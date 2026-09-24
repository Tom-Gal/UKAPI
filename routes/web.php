<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardPageController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::inertia('/api-catalogue', 'public/api-catalogue')->name('api-catalogue');
Route::inertia('/pricing', 'public/pricing')->name('pricing');
Route::inertia('/docs', 'public/docs')->name('docs');
Route::inertia('/docs/{section}', 'public/docs-section')->name('docs.section');
Route::inertia('/docs/api/{family}/{endpoint}', 'public/endpoint-reference')->name('docs.endpoint');
Route::inertia('/status', 'public/status')->name('status');
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
    Route::get('request-logs', [DashboardPageController::class, 'requestLogs'])->name('request-logs');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'overview'])->name('overview');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/usage', [AdminController::class, 'usage'])->name('usage');
    Route::get('/providers', [AdminController::class, 'providers'])->name('providers');
    Route::get('/errors', [AdminController::class, 'errors'])->name('errors');
    Route::get('/feature-flags', [AdminController::class, 'featureFlags'])->name('feature-flags');
});

require __DIR__.'/settings.php';
