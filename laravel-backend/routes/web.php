<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\AdController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AdBreakController;
use App\Http\Controllers\ReportDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Tenants
    Route::resource('tenants', TenantController::class);

    // Channels
    Route::resource('channels', ChannelController::class);
    
    // Ad Breaks (nested under channels)
    Route::resource('channels.ad-breaks', AdBreakController::class)->except(['index', 'show']);

    // Ads
    Route::resource('ads', AdController::class);
Route::post('ads/{ad}/rules', [App\Http\Controllers\AdRuleController::class, 'store'])->name('ads.rules.store');
Route::delete('ads/{ad}/rules/{rule}', [App\Http\Controllers\AdRuleController::class, 'destroy'])->name('ads.rules.destroy');

    // Campaigns
    Route::resource('campaigns', CampaignController::class);

    // API Keys
    Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::get('/api-keys/{tenant}', [ApiKeyController::class, 'show'])->name('api-keys.show');
    Route::post('/api-keys/{tenant}/regenerate', [ApiKeyController::class, 'regenerate'])->name('api-keys.regenerate');

    // Reports
    Route::get('/reports', [ReportDashboardController::class, 'index'])->name('reports.index');
    Route::get('/reports/data', [ReportDashboardController::class, 'getData'])->name('reports.data');
    Route::get('/reports/export', [ReportDashboardController::class, 'export'])->name('reports.export');
});
