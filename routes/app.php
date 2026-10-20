<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\AccountOrderController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\GuestOrderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromoCodeController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TelegramSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('welcome');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/catalog/{slug}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('terms');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout/quote', [CheckoutController::class, 'quote'])->name('checkout.quote');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/pay/{payment}', [CheckoutController::class, 'pay'])->name('checkout.pay');
Route::get('/pay/{payment}/status', [CheckoutController::class, 'status'])->name('checkout.status');
Route::post('/pay/{payment}/simulate', [CheckoutController::class, 'simulate'])->name('checkout.simulate');
Route::get('/order/{uuid}', [GuestOrderController::class, 'show'])->name('orders.show');
Route::get('/downloads/{slug}', [DownloadController::class, 'show'])->name('downloads.show');
Route::get('/downloads/{slug}/file', [DownloadController::class, 'file'])->name('downloads.file');

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user?->isStaff()) {
        return redirect()->to(locale_route('admin.dashboard'));
    }

    return redirect()->to(locale_route('account.orders'));
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('/account/orders', [AccountOrderController::class, 'index'])->name('account.orders');
    Route::get('/account/orders/{uuid}', [AccountOrderController::class, 'show'])->name('account.orders.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'staff'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/fulfillment', [OrderController::class, 'updateFulfillment'])->name('orders.fulfillment');

    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/promos', [PromoCodeController::class, 'index'])->name('promos');
    Route::post('/promos', [PromoCodeController::class, 'store'])->name('promos.store');
    Route::patch('/promos/{promo}', [PromoCodeController::class, 'update'])->name('promos.update');
    Route::delete('/promos/{promo}', [PromoCodeController::class, 'destroy'])->name('promos.destroy');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/telegram', [TelegramSettingsController::class, 'index'])->name('telegram');
    Route::post('/telegram/disconnect', [TelegramSettingsController::class, 'disconnect'])->name('telegram.disconnect');
});

require __DIR__.'/auth.php';
