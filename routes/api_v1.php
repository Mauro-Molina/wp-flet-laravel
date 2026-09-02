<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\BackupController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\CommandController;
use App\Http\Controllers\Api\V1\Content\CategoryController;
use App\Http\Controllers\Api\V1\Content\MediaController;
use App\Http\Controllers\Api\V1\Content\PageController;
use App\Http\Controllers\Api\V1\Content\PostController;
use App\Http\Controllers\Api\V1\Content\SettingController;
use App\Http\Controllers\Api\V1\Content\TagController;
use App\Http\Controllers\Api\V1\Content\WpUserController;
use App\Http\Controllers\Api\V1\LicenseController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\SecurityController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\UpdateController;
use App\Http\Controllers\Api\V1\UptimeController;
use App\Http\Middleware\AuditSensitiveRequest;
use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Support\Facades\Route;

Route::middleware([SecurityHeaders::class])->group(function (): void {
Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:auth-register')
        ->name('auth.register');
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:auth-login')
        ->name('auth.login');
    Route::post('2fa/verify', [AuthController::class, 'verifyTwoFactor'])
        ->middleware('throttle:auth-2fa')
        ->name('auth.2fa.verify');
    Route::post('refresh', [AuthController::class, 'refresh'])
        ->middleware('throttle:auth-refresh')
        ->name('auth.refresh');
});

Route::middleware([AuthenticateJwt::class, 'throttle:api-tenant', AuditSensitiveRequest::class])->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('switch-tenant', [AuthController::class, 'switchTenant'])->name('auth.switch-tenant');
        Route::post('2fa/setup', [AuthController::class, 'beginTwoFactorSetup'])->name('auth.2fa.setup');
        Route::post('2fa/enable', [AuthController::class, 'enableTwoFactor'])->name('auth.2fa.enable');
        Route::post('2fa/disable', [AuthController::class, 'disableTwoFactor'])->name('auth.2fa.disable');
    });

    Route::get('sites', [SiteController::class, 'index'])->name('sites.index');
    Route::post('sites', [SiteController::class, 'store'])->name('sites.store');
    Route::get('sites/{site}', [SiteController::class, 'show'])->name('sites.show');
    Route::patch('sites/{site}', [SiteController::class, 'update'])->name('sites.update');
    Route::delete('sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');
    Route::post('sites/{site}/connect', [SiteController::class, 'connect'])->name('sites.connect');
    Route::post('sites/{site}/disconnect', [SiteController::class, 'disconnect'])->name('sites.disconnect');
    Route::post('sites/{site}/credentials/regenerate', [SiteController::class, 'regenerateCredentials'])
        ->name('sites.credentials.regenerate');

    Route::get('sites/{site}/license', [LicenseController::class, 'validateSite'])->name('sites.license.validate');

    Route::get('sites/{site}/commands', [CommandController::class, 'index'])->name('commands.index');
    Route::post('sites/{site}/commands', [CommandController::class, 'store'])->name('commands.store');
    Route::get('commands/{command}', [CommandController::class, 'show'])->name('commands.show');

    Route::get('sites/{site}/updates', [UpdateController::class, 'index'])->name('updates.index');
    Route::post('sites/{site}/updates/run', [UpdateController::class, 'store'])->name('updates.run');
    Route::get('sites/{site}/updates/commands/{command}', [UpdateController::class, 'show'])->name('updates.show');

    Route::get('sites/{site}/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('sites/{site}/backups', [BackupController::class, 'store'])->name('backups.store');
    Route::get('sites/{site}/backups/{backup}', [BackupController::class, 'show'])->name('backups.show');
    Route::delete('sites/{site}/backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');
    Route::post('sites/{site}/backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');

    Route::get('sites/{site}/security/scans', [SecurityController::class, 'scans'])->name('security.scans');
    Route::get('sites/{site}/security/login-attempts', [SecurityController::class, 'loginAttempts'])->name('security.login_attempts');

    Route::get('sites/{site}/uptime/checks', [UptimeController::class, 'checks'])->name('uptime.checks');
    Route::get('sites/{site}/uptime/incidents', [UptimeController::class, 'incidents'])->name('uptime.incidents');

    Route::get('billing/status', [BillingController::class, 'status'])->name('billing.status');
    Route::get('billing/plans', [BillingController::class, 'plans'])->name('billing.plans');

    Route::get('notifications/preferences', [NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::put('notifications/preferences', [NotificationController::class, 'updatePreference'])->name('notifications.preferences.update');
    Route::get('notifications/history', [NotificationController::class, 'history'])->name('notifications.history');

    Route::prefix('sites/{site}/content')->group(function (): void {
        Route::get('posts', [PostController::class, 'index'])->name('content.posts.index');
        Route::post('posts', [PostController::class, 'store'])->name('content.posts.store');
        Route::get('posts/{post}', [PostController::class, 'show'])->name('content.posts.show')->whereNumber('post');
        Route::patch('posts/{post}', [PostController::class, 'update'])->name('content.posts.update')->whereNumber('post');
        Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('content.posts.destroy')->whereNumber('post');
        Route::post('posts/{post}/publish', [PostController::class, 'publish'])->name('content.posts.publish')->whereNumber('post');
        Route::post('posts/{post}/schedule', [PostController::class, 'schedule'])->name('content.posts.schedule')->whereNumber('post');

        Route::get('pages', [PageController::class, 'index'])->name('content.pages.index');
        Route::post('pages', [PageController::class, 'store'])->name('content.pages.store');
        Route::get('pages/{page}', [PageController::class, 'show'])->name('content.pages.show')->whereNumber('page');
        Route::patch('pages/{page}', [PageController::class, 'update'])->name('content.pages.update')->whereNumber('page');
        Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('content.pages.destroy')->whereNumber('page');

        Route::get('users', [WpUserController::class, 'index'])->name('content.users.index');
        Route::post('users', [WpUserController::class, 'store'])->name('content.users.store');
        Route::get('users/{user}', [WpUserController::class, 'show'])->name('content.users.show')->whereNumber('user');
        Route::patch('users/{user}', [WpUserController::class, 'update'])->name('content.users.update')->whereNumber('user');
        Route::delete('users/{user}', [WpUserController::class, 'destroy'])->name('content.users.destroy')->whereNumber('user');

        Route::get('categories', [CategoryController::class, 'index'])->name('content.categories.index');
        Route::post('categories', [CategoryController::class, 'store'])->name('content.categories.store');

        Route::get('tags', [TagController::class, 'index'])->name('content.tags.index');
        Route::post('tags', [TagController::class, 'store'])->name('content.tags.store');

        Route::get('media', [MediaController::class, 'index'])->name('content.media.index');
        Route::post('media', [MediaController::class, 'store'])->name('content.media.store');
        Route::get('media/{media}', [MediaController::class, 'show'])->name('content.media.show')->whereNumber('media');
        Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('content.media.destroy')->whereNumber('media');

        Route::get('settings', [SettingController::class, 'show'])->name('content.settings.show');
        Route::patch('settings', [SettingController::class, 'update'])->name('content.settings.update');
    });
});
});
