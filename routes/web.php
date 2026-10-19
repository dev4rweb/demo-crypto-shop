<?php

use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

$locales = implode('|', config('localization.supported'));

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', function () {
    return response((string) file_get_contents(public_path('robots.txt')), 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
    ]);
});
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');
Route::post('/webhooks/nowpayments', [WebhookController::class, 'nowpayments'])->name('webhooks.nowpayments');
Route::post('/webhooks/cryptomus', [WebhookController::class, 'cryptomus'])->name('webhooks.cryptomus');
Route::post('/webhooks/testnet', [WebhookController::class, 'testnet'])->name('webhooks.testnet');

require __DIR__.'/app.php';

Route::prefix('{locale}')
    ->where(['locale' => $locales])
    ->name('locale.')
    ->group(function (): void {
        require __DIR__.'/app.php';
    });
