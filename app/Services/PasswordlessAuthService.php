<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordlessAuthService
{
    public function __construct(
        private GuestOrderClaimService $claim,
    ) {}

    /**
     * Log in a customer via Google or a magic link. Staff must use a password.
     *
     * @throws ValidationException
     */
    public function loginCustomer(string $email, ?string $name = null): User
    {
        $email = strtolower(trim($email));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user?->isStaff()) {
            throw ValidationException::withMessages([
                'email' => __('app.auth.passwordless_staff'),
            ]);
        }

        if (! $user) {
            $user = new User;
            $user->forceFill([
                'name' => $this->displayName($name, $email),
                'email' => $email,
                'password' => Hash::make(Str::password(32)),
                'role' => 'customer',
                'email_verified_at' => now(),
            ])->save();
        } else {
            $updates = [];

            if (! $user->email_verified_at) {
                $updates['email_verified_at'] = now();
            }

            if ($name && trim($user->name) === '') {
                $updates['name'] = $this->displayName($name, $email);
            }

            if ($updates !== []) {
                $user->forceFill($updates)->save();
            }
        }

        Auth::login($user, true);
        request()->session()->regenerate();
        $this->claim->claim($user);

        return $user->fresh() ?? $user;
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
    }

    private function displayName(?string $name, string $email): string
    {
        $name = trim((string) $name);

        if ($name !== '') {
            return $name;
        }

        return (string) Str::of($email)->before('@')->replace(['.', '_', '-'], ' ')->title();
    }
}
