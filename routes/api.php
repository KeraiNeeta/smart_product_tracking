
<?php

use App\Http\Controllers\Api\V1\EndUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/end-user')->name('api.v1.end-user.')->group(function (): void {
    Route::post('/register', [EndUserController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register');
    Route::post('/login', [EndUserController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login');

    Route::middleware(['auth:sanctum', 'end-customer.api'])->group(function (): void {
        Route::post('/logout', [EndUserController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [EndUserController::class, 'dashboard'])->name('dashboard');
        Route::get('/device', [EndUserController::class, 'device'])->name('device');
        Route::post('/device/monitoring-state', [EndUserController::class, 'setMonitoringState'])
            ->middleware('throttle:20,1')
            ->name('device.monitoring-state');
        Route::get('/notifications', [EndUserController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/read', [EndUserController::class, 'markNotificationsRead'])->name('notifications.read');
        Route::get('/profile', [EndUserController::class, 'profile'])->name('profile');
        Route::patch('/profile', [EndUserController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [EndUserController::class, 'updatePassword'])->name('profile.password');
        Route::post('/push-token', [EndUserController::class, 'registerPushToken'])->name('push-token.store');
        Route::delete('/push-token', [EndUserController::class, 'deletePushToken'])->name('push-token.destroy');
    });
});