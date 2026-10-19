<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MagicLinkMail;
use App\Models\MagicLoginToken;
use App\Services\PasswordlessAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class MagicLinkController extends Controller
{
    public function send(Request $request, PasswordlessAuthService $auth): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'intended' => ['nullable', 'string', 'max:500'],
        ]);

        $email = strtolower($data['email']);
        $key = 'magic-link:'.$request->ip().':'.$email;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'email' => __('app.auth.magic_throttled'),
            ]);
        }

        RateLimiter::hit($key, 15 * 60);

        $existing = $auth->findByEmail($email);

        if (! $existing?->isStaff()) {
            MagicLoginToken::query()
                ->where('email', $email)
                ->whereNull('used_at')
                ->delete();

            $plain = Str::random(48);
            $redirectTo = $this->safeIntended($data['intended'] ?? null);

            MagicLoginToken::query()->create([
                'email' => $email,
                'token' => hash('sha256', $plain),
                'redirect_to' => $redirectTo,
                'expires_at' => now()->addMinutes(15),
            ]);

            $url = URL::temporarySignedRoute('auth.magic.consume', now()->addMinutes(15), [
                'token' => $plain,
            ]);

            try {
                Mail::to($email)->send(new MagicLinkMail($url));
            } catch (Throwable $e) {
                Log::error('Failed to send MagicLinkMail', [
                    'email' => $email,
                    'message' => $e->getMessage(),
                ]);

                return back()->with('error', __('app.auth.magic_failed'));
            }
        }

        return back()->with('success', __('app.auth.magic_sent'));
    }

    public function consume(Request $request, string $token, PasswordlessAuthService $auth): RedirectResponse
    {
        $record = MagicLoginToken::query()
            ->where('token', hash('sha256', $token))
            ->first();

        if (! $record || ! $record->isUsable()) {
            return redirect()->route('login')->with('error', __('app.auth.magic_invalid'));
        }

        $record->forceFill(['used_at' => now()])->save();

        if (is_string($record->redirect_to) && $record->redirect_to !== '') {
            $request->session()->put('url.intended', $record->redirect_to);
        }

        try {
            $auth->loginCustomer($record->email);
        } catch (ValidationException $e) {
            return redirect()->route('login')->with('error', collect($e->errors())->flatten()->first() ?: __('app.auth.passwordless_staff'));
        }

        return redirect()->intended(locale_route('dashboard'));
    }

    private function safeIntended(?string $intended): ?string
    {
        $intended = $intended ?: null;

        if (! is_string($intended) || $intended === '') {
            return null;
        }

        $app = rtrim((string) config('app.url'), '/');

        if (str_starts_with($intended, '/') || ($app !== '' && str_starts_with($intended, $app))) {
            return $intended;
        }

        return null;
    }
}
