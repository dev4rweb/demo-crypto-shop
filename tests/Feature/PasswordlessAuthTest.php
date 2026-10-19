<?php

use App\Mail\MagicLinkMail;
use App\Models\MagicLoginToken;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function enableGoogle(): void
{
    config([
        'services.google.client_id' => 'test-google-id',
        'services.google.client_secret' => 'test-google-secret',
        'services.google.redirect' => 'http://localhost/auth/google/callback',
    ]);
}

function mockGoogleUser(string $email, string $name = 'Ada Lovelace'): void
{
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getEmail')->andReturn($email);
    $socialiteUser->shouldReceive('getName')->andReturn($name);

    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

function sendMagicLink(string $email): string
{
    $url = '';

    Mail::fake();

    test()->post('/auth/magic', ['email' => $email])->assertRedirect();

    Mail::assertSent(MagicLinkMail::class, function (MagicLinkMail $mail) use (&$url) {
        $url = $mail->loginUrl;

        return $url !== '';
    });

    return $url;
}

test('a valid magic link creates a verified buyer and claims guest orders', function () {
    $payment = placeDemoPayment('linkme@example.com');
    $url = sendMagicLink('linkme@example.com');

    $this->get($url)->assertRedirect();
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'linkme@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe('customer')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($payment->order->fresh()->user_id)->toBe($user->id);
});

test('an expired magic link is rejected', function () {
    $url = sendMagicLink('expire@example.com');

    $this->travel(16)->minutes();

    $this->get($url)->assertForbidden();
    $this->assertGuest();
    expect(User::query()->where('email', 'expire@example.com')->exists())->toBeFalse();
});

test('a reused magic link is rejected', function () {
    $url = sendMagicLink('reuse@example.com');

    $this->get($url)->assertRedirect();
    $this->assertAuthenticated();
    $this->post('/logout');
    $this->assertGuest();

    $this->get($url)->assertRedirect(route('login'));
    $this->assertGuest();
});

test('staff cannot request or consume a magic link', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin-staff@example.com']);

    Mail::fake();
    $this->post('/auth/magic', ['email' => $admin->email])->assertRedirect();
    Mail::assertNothingSent();
    $this->assertGuest();

    $plain = Str::random(48);
    MagicLoginToken::query()->create([
        'email' => $admin->email,
        'token' => hash('sha256', $plain),
        'expires_at' => now()->addMinutes(15),
    ]);

    $url = URL::temporarySignedRoute('auth.magic.consume', now()->addMinutes(15), [
        'token' => $plain,
    ]);

    $this->get($url)->assertRedirect(route('login'));
    $this->assertGuest();
});

test('google callback creates a buyer and links guest orders', function () {
    enableGoogle();
    $payment = placeDemoPayment('ada@example.com');
    mockGoogleUser('ada@example.com', 'Ada Lovelace');

    $this->get('/auth/google/callback')->assertRedirect();
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'ada@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Ada Lovelace')
        ->and($user->role)->toBe('customer')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($payment->order->fresh()->user_id)->toBe($user->id);
});

test('google callback logs into an existing buyer', function () {
    enableGoogle();
    $user = User::factory()->create(['email' => 'buyer-google@example.com']);
    mockGoogleUser('buyer-google@example.com', 'Ignored Name');

    $this->get('/auth/google/callback')->assertRedirect();
    $this->assertAuthenticated();
    expect(auth()->id())->toBe($user->id)
        ->and(User::query()->where('email', 'buyer-google@example.com')->count())->toBe(1);
});

test('staff cannot sign in with google', function () {
    enableGoogle();
    User::factory()->manager()->create(['email' => 'manager-google@example.com']);
    mockGoogleUser('manager-google@example.com');

    $this->get('/auth/google/callback')->assertRedirect(route('login'));
    $this->assertGuest();
});

test('google routes are hidden when keys are empty', function () {
    $this->get('/auth/google')->assertNotFound();
    $this->get('/auth/google/callback')->assertNotFound();
    $this->post('/auth/google/one-tap', ['credential' => 'x'])->assertNotFound();

    $this->get('/login')->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('googleEnabled', false)
            ->where('googleClientId', null));
});
