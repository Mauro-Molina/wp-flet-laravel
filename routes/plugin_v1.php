<?php

use App\Http\Controllers\Api\V1\Plugin\EventController;
use App\Http\Controllers\Api\V1\Plugin\HeartbeatController;
use App\Http\Controllers\Api\V1\Plugin\PluginCommandController;
use App\Http\Controllers\Api\V1\Plugin\PluginIngestController;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerifyPluginHmac;
use Illuminate\Support\Facades\Route;

Route::middleware([SecurityHeaders::class, 'throttle:plugin', VerifyPluginHmac::class])->group(function (): void {
    Route::post('heartbeat', [HeartbeatController::class, 'store'])->name('plugin.heartbeat');
    Route::post('events', [EventController::class, 'store'])->name('plugin.events.ingest');
    Route::post('commands/{command}/complete', [PluginCommandController::class, 'complete'])
        ->name('plugin.commands.complete');
    Route::post('commands/{command}/fail', [PluginCommandController::class, 'fail'])
        ->name('plugin.commands.fail');

    Route::post('updates/sync', [PluginIngestController::class, 'syncUpdates'])->name('plugin.updates.sync');
    Route::post('security/scans', [PluginIngestController::class, 'securityScan'])->name('plugin.security.scan');
    Route::post('security/login-attempts', [PluginIngestController::class, 'loginAttempts'])->name('plugin.security.login_attempts');
    Route::post('uptime/checks', [PluginIngestController::class, 'uptime'])->name('plugin.uptime.check');
});
