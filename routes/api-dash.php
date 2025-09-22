<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\Dash\MediaController;
use App\Http\Controllers\Dash\ConfigController;
use App\Http\Controllers\Dash\DashboardController;
use App\Http\Controllers\Dash\DashboardWebdeveloperController;
use App\Http\Controllers\Dash\OptionsController;
use App\Http\Controllers\Dash\NotificationsController;
use App\Http\Controllers\Dash\PermissionsController;
use App\Http\Controllers\Dash\RolesController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResources([
        'users'         => UsersController::class,
        'permissions'   => PermissionsController::class,
        'roles'         => RolesController::class,
        'dash/media'    => MediaController::class,
    ]);

    //dashboard
    Route::get('dashboard/welcome', [DashboardController::class, 'welcome']);
    Route::get('dashboard/chart_bulanini', [DashboardController::class, 'chart_bulanini']);
    Route::get('dashboard/chart_tahunini', [DashboardController::class, 'chart_tahunini']);
    Route::get('dashboard/chart_hariini', [DashboardController::class, 'chart_hariini']);
    Route::get('dashboard/datatable', [DashboardController::class, 'datatable']);
    Route::get('dashboard/chart_project_tahun_terakhir', [DashboardController::class, 'chart_project_tahun_terakhir']);

    //dashboard webdeveloper
    Route::get('dashboard/welcome_webdeveloper', [DashboardWebdeveloperController::class, 'welcome']);
    Route::get('dashboard/chart_project_tahun_terakhir_webdeveloper', [DashboardWebdeveloperController::class, 'chart_project_tahun_terakhir_webdeveloper']);
    Route::get('dashboard/chart_project_saat_ini_webdeveloper', [DashboardWebdeveloperController::class, 'chart_project_saat_ini_webdeveloper']);

    //options
    Route::get('option/{key}', [OptionsController::class, 'get']);

    //notifications
    Route::get('notifications', [NotificationsController::class, 'index']);
    Route::post('notifications/mark-as-read', [NotificationsController::class, 'markAsRead']);
    Route::post('notifications/mark-all-as-read', [NotificationsController::class, 'markAllAsRead']);

    Route::put('user/updatepassword/{id}', [UsersController::class, 'updatePassword']);
    Route::put('user/updateavatar/{id}', [UsersController::class, 'updateAvatar']);
    Route::post('dash/setconfig', [ConfigController::class, 'setconfig']);
});

Route::get('dash/config', [ConfigController::class, 'index']);
