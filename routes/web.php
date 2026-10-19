<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MasterAdmin\AlertManagementController;
use App\Http\Controllers\MasterAdmin\CompanyManagementController;
use App\Http\Controllers\MasterAdmin\DashboardController;
use App\Http\Controllers\MasterAdmin\DeviceManagementController;
use App\Http\Controllers\MasterAdmin\MonitoringManagementController;
use App\Http\Controllers\MasterAdmin\NotificationRuleManagementController;
use App\Http\Controllers\MasterAdmin\NotificationProviderSettingsController;
use App\Http\Controllers\MasterAdmin\OperationsController;
use App\Http\Controllers\Auth\MasterAdminAuthController;
use App\Http\Controllers\Auth\EndCustomerRegistrationController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\MasterAdmin\RoleManagementController;
use App\Http\Controllers\MasterAdmin\ShopManagementController;
use App\Http\Controllers\MasterAdmin\UserManagementController;
use App\Http\Controllers\Portal\RolePortalController;

Route::get('/', function () {
    return redirect()->route('master-admin.login');
});

Route::get('/login', [
    MasterAdminAuthController::class,
    'showLogin',
])->name('master-admin.login');

Route::post('/login', [
    MasterAdminAuthController::class,
    'login',
])->name('master-admin.login.submit');

Route::get('/register', [EndCustomerRegistrationController::class, 'show'])
    ->name('register');
Route::post('/register', [EndCustomerRegistrationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('register.submit');

Route::middleware('auth')->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [RolePortalController::class, 'dashboard'])->name('dashboard');
    Route::post('/devices/{device}/monitoring-state', [RolePortalController::class, 'setMonitoringState'])
        ->whereNumber('device')
        ->name('devices.monitoring-state');
    Route::get('/notifications', [RolePortalController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/read', [RolePortalController::class, 'markNotificationsRead'])->name('notifications.read');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/logout', [MasterAdminAuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'superadmin'])
    ->prefix('master-admin')
    ->name('master-admin.')
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        Route::resource('companies', CompanyManagementController::class)->except('show');
        Route::resource('shops', ShopManagementController::class)->except('show');
        Route::resource('devices', DeviceManagementController::class)->except(['show', 'destroy']);
        Route::get('/devices/{device}/transfer', [DeviceManagementController::class, 'transferForm'])->name('devices.transfer-form');
        Route::post('/devices/{device}/transfer', [DeviceManagementController::class, 'transfer'])->name('devices.transfer');
        Route::resource('users', UserManagementController::class)
            ->except('show');
        Route::resource('roles', RoleManagementController::class)
            ->except('show');

        Route::get('/configurations', [MonitoringManagementController::class, 'configurations'])->name('configurations.index');
        Route::get('/configurations/create', [MonitoringManagementController::class, 'createConfiguration'])->name('configurations.create');
        Route::post('/configurations', [MonitoringManagementController::class, 'storeConfiguration'])->name('configurations.store');
        Route::get('/schedules', [MonitoringManagementController::class, 'schedules'])->name('schedules.index');
        Route::get('/schedules/create', [MonitoringManagementController::class, 'createSchedule'])->name('schedules.create');
        Route::post('/schedules', [MonitoringManagementController::class, 'storeSchedule'])->name('schedules.store');
        Route::get('/schedules/{schedule}/edit', [MonitoringManagementController::class, 'editSchedule'])->name('schedules.edit');
        Route::put('/schedules/{schedule}', [MonitoringManagementController::class, 'updateSchedule'])->name('schedules.update');
        Route::delete('/schedules/{schedule}', [MonitoringManagementController::class, 'destroySchedule'])->name('schedules.destroy');

        Route::get('/alerts', [AlertManagementController::class, 'index'])->name('alerts.index');
        Route::post('/alerts/{alert}/acknowledge', [AlertManagementController::class, 'acknowledge'])->name('alerts.acknowledge');
        Route::post('/alerts/{alert}/resolve', [AlertManagementController::class, 'resolve'])->name('alerts.resolve');

        Route::resource('notification-rules', NotificationRuleManagementController::class)
            ->except('show');
        Route::get('/notification-providers', [NotificationProviderSettingsController::class, 'index'])->name('notification-providers.index');
        Route::put('/notification-providers/{channel}', [NotificationProviderSettingsController::class, 'update'])->whereIn('channel', ['EMAIL', 'SMS', 'WHATSAPP', 'PUSH'])->name('notification-providers.update');
        Route::post('/notification-providers/{channel}/test', [NotificationProviderSettingsController::class, 'test'])->whereIn('channel', ['EMAIL', 'SMS', 'WHATSAPP', 'PUSH'])->name('notification-providers.test');
        Route::get('/operations/{module}', [OperationsController::class, 'index'])
            ->whereIn('module', ['inventory', 'events', 'heartbeats', 'commands', 'notification-jobs', 'notification-logs', 'push-tokens', 'audit'])
            ->name('operations.index');

        Route::post('/logout', [
            MasterAdminAuthController::class,
            'logout',
        ])->name('logout');
    });

Route::get('/db-check', function () {
    return response()->json([
        'environment' => app()->environment(),
        'env_connection' => env('DB_CONNECTION'),
        'default_connection' => config('database.default'),
        'pgsql_database' => config('database.connections.pgsql.database'),
        'sqlite_database' => config('database.connections.sqlite.database'),
    ]);
});
