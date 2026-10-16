<?php

namespace App\Http\Controllers;

use App\Services\TelegramLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TelegramSettingsController extends Controller
{
    public function index(Request $request, TelegramLinkService $links): Response
    {
        $user = $request->user();
        $token = $links->ensureToken($user);

        return Inertia::render('Admin/Telegram', [
            'seo' => page_seo(__('app.telegram.meta_title'), __('app.telegram.meta_description')),
            'connected' => (bool) $user->telegram_chat_id,
            'connectUrl' => $links->deepLink($token),
            'enabled' => telegram_connect_ready(),
        ]);
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $request->user()->update([
            'telegram_chat_id' => null,
            'telegram_connect_token' => null,
        ]);

        return back()->with('success', __('app.telegram.disconnected'));
    }
}
