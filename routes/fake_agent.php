<?php

use App\Http\Controllers\FakeAgent\FakeAgentController;
use App\Http\Middleware\VerifyPluginHmac;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifyPluginHmac::class)->group(function (): void {
    Route::get('posts', [FakeAgentController::class, 'listPosts']);
    Route::post('posts', [FakeAgentController::class, 'createPost']);
    Route::get('posts/{id}', [FakeAgentController::class, 'showPost'])->whereNumber('id');
    Route::match(['patch', 'put'], 'posts/{id}', [FakeAgentController::class, 'updatePost'])->whereNumber('id');
    Route::delete('posts/{id}', [FakeAgentController::class, 'deletePost'])->whereNumber('id');
    Route::post('posts/{id}/publish', [FakeAgentController::class, 'publishPost'])->whereNumber('id');
    Route::post('posts/{id}/schedule', [FakeAgentController::class, 'schedulePost'])->whereNumber('id');

    Route::get('pages', [FakeAgentController::class, 'listPages']);
    Route::post('pages', [FakeAgentController::class, 'createPage']);
    Route::get('pages/{id}', [FakeAgentController::class, 'showPage'])->whereNumber('id');
    Route::match(['patch', 'put'], 'pages/{id}', [FakeAgentController::class, 'updatePage'])->whereNumber('id');
    Route::delete('pages/{id}', [FakeAgentController::class, 'deletePage'])->whereNumber('id');

    Route::get('users', [FakeAgentController::class, 'listUsers']);
    Route::post('users', [FakeAgentController::class, 'inviteUser']);
    Route::get('users/{id}', [FakeAgentController::class, 'showUser'])->whereNumber('id');
    Route::match(['patch', 'put'], 'users/{id}', [FakeAgentController::class, 'updateUser'])->whereNumber('id');
    Route::delete('users/{id}', [FakeAgentController::class, 'deleteUser'])->whereNumber('id');

    Route::get('settings', [FakeAgentController::class, 'getSettings']);
    Route::match(['patch', 'put'], 'settings', [FakeAgentController::class, 'updateSettings']);
});
