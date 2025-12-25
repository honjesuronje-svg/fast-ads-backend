<?php

use App\Http\Controllers\AdDecisionController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\ChannelInfoController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware(['api.key'])->group(function () {
    
    // Ads Decision API (called by Golang)
    Route::post('/ads/decision', [AdDecisionController::class, 'decision'])
        ->name('ads.decision');
    
    // VMAP/VAST Generation (CSAI)
    Route::get('/ads/vmap/{tenant_slug}/{channel_slug}', [AdDecisionController::class, 'vmap'])
        ->name('ads.vmap');
    
    Route::get('/ads/vast/{tenant_slug}/{channel_slug}', [AdDecisionController::class, 'vast'])
        ->name('ads.vast');
    
    // Tracking Events
    Route::post('/tracking/events', [TrackingController::class, 'events'])
        ->name('tracking.events');
    
    // Channel info endpoint (for Golang SSAI service)
    Route::get('/channels/{tenant}/{channel}', [ChannelInfoController::class, 'show'])
        ->name('channels.info');
    
    // Reporting endpoints
    Route::get('/reports', [ReportController::class, 'getReport'])
        ->name('reports.get');
    
    Route::get('/reports/export', [ReportController::class, 'exportReport'])
        ->name('reports.export');
});

