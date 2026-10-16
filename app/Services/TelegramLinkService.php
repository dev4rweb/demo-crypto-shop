<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class TelegramLinkService
{
    public function ensureToken(User $user): ?string
    {
        if (! telegram_connect_ready()) {
            return null;
        }

        if ($user->telegram_chat_id) {
            return null;
        }

        if (! $user->telegram_connect_token) {
            $user->update([
                'telegram_connect_token' => Str::lower(Str::random(24)),
            ]);
        }

        return $user->telegram_connect_token;
    }

    public function deepLink(?string $token): ?string
    {
        if (! $token || ! telegram_connect_ready()) {
            return null;
        }

        $username = ltrim((string) config('services.telegram.bot_username'), '@');

        return 'https://t.me/'.$username.'?start='.$token;
    }

    public function connectChat(string $token): ?User
    {
        $token = trim($token);

        if ($token === '') {
            return null;
        }

        return User::query()->where('telegram_connect_token', $token)->first();
    }
}
