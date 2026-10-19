<?php

use App\Models\User;

test('telegram webhook links a staff chat with the start token', function () {
    $admin = User::factory()->admin()->create([
        'telegram_connect_token' => 'abc123connecttoken',
    ]);

    $this->postJson('/telegram/webhook', [
        'message' => [
            'text' => '/start abc123connecttoken',
            'chat' => ['id' => 4242],
        ],
    ])->assertOk();

    expect($admin->fresh()->telegram_chat_id)->toBe('4242')
        ->and($admin->fresh()->telegram_connect_token)->toBeNull();
});
