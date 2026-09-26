<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardPageController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PublicStatusController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::get('/ready', HealthController::class)->name('ready');
Route::inertia('/api-catalogue', 'public/api-catalogue')->name('api-catalogue');
Route::inertia('/pricing', 'public/pricing')->name('pricing');
Route::inertia('/docs', 'public/docs')->name('docs');
Route::inertia('/try-it', 'public/try-it')->name('try-it');
Route::inertia('/docs/{section}', 'public/docs-section')->name('docs.section');
Route::inertia('/docs/api/{family}/{endpoint}', 'public/endpoint-reference')->name('docs.endpoint');
Route::get('/status', PublicStatusController::class)->name('status');
Route::inertia('/terms', 'public/legal')->defaults('document', 'terms')->name('terms');
Route::inertia('/privacy', 'public/legal')->defaults('document', 'privacy')->name('privacy');
Route::inertia('/acceptable-use', 'public/legal')->defaults('document', 'acceptable-use')->name('acceptable-use');
Route::inertia('/data-sources', 'public/data-sources')->name('data-sources');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');
    Route::get('usage', [DashboardPageController::class, 'usage'])->name('usage');
    Route::get('billing', [DashboardPageController::class, 'billing'])->name('billing');
    Route::post('billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'overview'])->name('overview');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/providers', [AdminController::class, 'providers'])->name('providers');
});

require __DIR__.'/settings.php';
