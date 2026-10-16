<?php

namespace App\Http\Controllers;

use App\Services\TelegramLinkService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramService $telegram, TelegramLinkService $links): JsonResponse
    {
        $secret = (string) config('services.telegram.webhook_secret');
        if ($secret !== '') {
            abort_unless(hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 401);
        }

        $text = (string) data_get($request->all(), 'message.text', '');
        $chatId = data_get($request->all(), 'message.chat.id');

        if (! $chatId || ! str_starts_with($text, '/start')) {
            return response()->json(['ok' => true]);
        }

        $token = trim(substr($text, strlen('/start')));
        if ($token === '') {
            $telegram->sendMessage((string) $chatId, __('app.telegram.bot_hello'));

            return response()->json(['ok' => true]);
        }

        $target = $links->connectChat($token);

        if (! $target) {
            $telegram->sendMessage((string) $chatId, __('app.telegram.bot_unknown'));

            return response()->json(['ok' => true]);
        }

        $target->update([
            'telegram_chat_id' => (string) $chatId,
            'telegram_connect_token' => null,
        ]);

        $telegram->sendMessage((string) $chatId, __('app.telegram.bot_connected', ['name' => $target->name]));

        return response()->json(['ok' => true]);
    }
}
