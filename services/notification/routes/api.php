<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// Internal push — authenticated by X-Internal-Secret header (no user token needed)
Route::middleware(['internal.secret'])->group(function () {
    Route::post('/internal/push', [NotificationController::class, 'push']);
});

// User-facing — authenticated by Bearer JWT token
Route::middleware(['auth.internal'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::patch('/notifications/{id}/state', [NotificationController::class, 'updateState']);
});
