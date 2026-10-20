<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordlessAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request)
    {
        abort_unless(google_oauth_ready(), 404);

        $this->rememberIntended($request);

        return Socialite::driver('google')->redirect();
    }

    public function callback(PasswordlessAuthService $auth): RedirectResponse
    {
        abort_unless(google_oauth_ready(), 404);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::error('Google OAuth callback failed', ['message' => $e->getMessage()]);

            return redirect()->route('login')->with('error', __('app.auth.google_failed'));
        }

        return $this->complete(
            $auth,
            (string) $googleUser->getEmail(),
            $googleUser->getName(),
        );
    }

    public function oneTap(Request $request, PasswordlessAuthService $auth): RedirectResponse
    {
        abort_unless(google_client_id(), 404);

        $data = $request->validate([
            'credential' => ['required', 'string'],
        ]);

        $payload = $this->verifyIdToken($data['credential']);

        if ($payload === null) {
            return redirect()->route('login')->with('error', __('app.auth.google_failed'));
        }

        $this->rememberIntended($request);

        return $this->complete(
            $auth,
            (string) ($payload['email'] ?? ''),
            isset($payload['name']) ? (string) $payload['name'] : null,
        );
    }

    private function complete(PasswordlessAuthService $auth, string $email, ?string $name): RedirectResponse
    {
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('login')->with('error', __('app.auth.google_failed'));
        }

        try {
            $auth->loginCustomer($email, $name);
        } catch (ValidationException $e) {
            return redirect()->route('login')->with('error', collect($e->errors())->flatten()->first() ?: __('app.auth.passwordless_staff'));
        }

        return redirect()->intended(locale_route('dashboard'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function verifyIdToken(string $jwt): ?array
    {
        try {
            $response = Http::timeout(8)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $jwt,
            ]);
        } catch (Throwable $e) {
            Log::error('Google One Tap token lookup failed', ['message' => $e->getMessage()]);

            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return null;
        }

        $audience = (string) ($payload['aud'] ?? '');
        $email = (string) ($payload['email'] ?? '');
        $verified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($audience !== (string) google_client_id() || $email === '' || ! $verified) {
            return null;
        }

        return $payload;
    }

    private function rememberIntended(Request $request): void
    {
        $intended = $request->input('intended') ?: $request->query('intended');

        if (! is_string($intended) || $intended === '') {
            return;
        }

        $app = rtrim((string) config('app.url'), '/');

        if (str_starts_with($intended, '/') || ($app !== '' && str_starts_with($intended, $app))) {
            $request->session()->put('url.intended', $intended);
        }
    }
}
