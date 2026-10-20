<?php

use App\Mail\PaymentConfirmedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

test('payment confirmed mail is sent after simulated payment', function () {
    Mail::fake();

    $payment = placeDemoPayment();

    $this->postJson('/pay/'.$payment->uuid.'/simulate')->assertOk();
    $this->travel(10)->seconds();
    app(\App\Services\PaymentMonitorService::class)->advanceSimulation($payment->fresh('order.items'));

    Mail::assertSent(PaymentConfirmedMail::class, function (PaymentConfirmedMail $mail) use ($payment) {
        return $mail->order->uuid === $payment->order->uuid;
    });
});

test('guest can simulate payment by uuid and receive the paid email', function () {
    Mail::fake();

    $product = seedCatalog();

    $this->assertGuest();

    $this->post('/checkout', [
        'name' => 'Jordan Hale',
        'email' => 'guest-sim@example.com',
        'delivery_type' => 'digital',
        'coin' => 'ETH',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertRedirect();

    $this->assertGuest();

    $payment = \App\Models\Payment::query()->latest('id')->first();
    expect($payment)->not->toBeNull()
        ->and($payment->uuid)->not->toBeEmpty()
        ->and($payment->order->user_id)->toBeNull()
        ->and($payment->order->email)->toBe('guest-sim@example.com');

    $this->get('/pay/'.$payment->id)->assertNotFound();
    $this->get('/pay/'.$payment->uuid)
        ->assertOk()
        ->assertSee('Simulate payment', false);

    $this->postJson('/pay/'.$payment->id.'/simulate')->assertNotFound();
    $this->post('/pay/'.$payment->uuid.'/simulate', [], [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
    ])->assertOk()
        ->assertJsonPath('uuid', $payment->uuid)
        ->assertJsonPath('status', 'detected');

    $this->travel(10)->seconds();

    $this->getJson('/pay/'.$payment->uuid.'/status')
        ->assertOk()
        ->assertJsonPath('status', 'confirmed')
        ->assertJsonPath('order.status', 'paid');

    $this->assertGuest();

    expect($payment->fresh('order.items')->order->items->first()->delivery_payload['key'] ?? null)->not->toBeNull();

    Mail::assertSent(PaymentConfirmedMail::class, function (PaymentConfirmedMail $mail) use ($payment) {
        return $mail->order->uuid === $payment->order->uuid
            && $mail->hasTo('guest-sim@example.com');
    });
});

test('signed order page shows status and digital goods', function () {
    Mail::fake();

    $payment = confirmDemoPayment();
    $order = $payment->order;
    $url = URL::signedRoute('orders.show', ['uuid' => $order->uuid]);
    $key = $order->items->first()->delivery_payload['key'] ?? null;

    $this->get($url)
        ->assertOk()
        ->assertSee($order->uuid, false)
        ->assertSee($order->status, false);

    expect($key)->not->toBeNull();
    $this->get($url)->assertSee($key, false);
});

test('unsigned order page is denied', function () {
    Mail::fake();

    $payment = confirmDemoPayment();

    $this->get('/order/'.$payment->order->uuid)->assertForbidden();
});

test('login attaches guest orders with matching email', function () {
    Mail::fake();

    $payment = placeDemoPayment('claim@example.com');
    expect($payment->order->user_id)->toBeNull();

    $user = User::factory()->create(['email' => 'claim@example.com']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    expect($payment->order->fresh()->user_id)->toBe($user->id);
});

test('registration attaches guest orders with matching email', function () {
    Mail::fake();

    $payment = placeDemoPayment('newbuyer@example.com');
    expect($payment->order->user_id)->toBeNull();

    $this->post('/register', [
        'name' => 'New Buyer',
        'email' => 'newbuyer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    expect($payment->order->fresh()->user_id)->not->toBeNull();
});

test('signed download page and file are available', function () {
    $product = seedCatalog();
    $page = URL::signedRoute('downloads.show', ['slug' => $product->slug]);
    $file = URL::signedRoute('downloads.file', ['slug' => $product->slug]);

    $this->get($page)->assertOk()->assertSee('neon-storefront-kit', false);

    $download = $this->get($file);
    $download->assertOk();
    expect($download->streamedContent())->toContain('Nexus demo download');
});

test('unsigned download is denied', function () {
    seedCatalog();

    $this->get('/downloads/neon-storefront-kit')->assertForbidden();
    $this->get('/downloads/neon-storefront-kit/file')->assertForbidden();
});

test('order owner can open the download page', function () {
    Mail::fake();

    $payment = confirmDemoPayment('owner@example.com');
    $user = User::factory()->create(['email' => 'owner@example.com']);
    app(\App\Services\GuestOrderClaimService::class)->claim($user);

    $this->actingAs($user)
        ->get('/downloads/neon-storefront-kit')
        ->assertOk();
});

test('login page lists demo logins from the shared prop', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('buyer@example.com', false)
        ->assertSee('admin@example.com', false)
        ->assertSee('manager@example.com', false)
        ->assertSee('demo123456', false);
});

test('checkout page mentions guest checkout and the buyer demo login', function () {
    $this->get('/checkout')
        ->assertOk()
        ->assertSee('buyer@example.com', false)
        ->assertSee('demo123456', false);
});
