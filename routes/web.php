<?php

use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\NotificationCenterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('notifications.index')
        : redirect()->route('login');
});

Route::middleware(['auth', 'hotel.context'])->group(function () {
    Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/click', [NotificationCenterController::class, 'click'])->name('notifications.click');
    Route::patch('/notifications/mark-all-read', [NotificationCenterController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
});

Route::get('/health', HealthCheckController::class)->name('health');
