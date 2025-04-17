<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OptionsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationsController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    // return $request->user();
    $results = $request->user();

    // Dapatkan semua permissions
    $permissons = $request->user()->getPermissionsViaRoles();

    //collection permissions
    $results['user_permissions'] = collect($permissons)->pluck('name');

    unset($results->roles);

    return $results;
});

Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResources([
        'roles'         => RolesController::class,
        'permissions'   => PermissionsController::class,
        'posts'         => PostsController::class,
        'terms'         => TermsController::class
    ]);
    Route::get('option/{key}', [OptionsController::class, 'get']);

    //dashboard
    Route::get('dashboard/datatable', [DashboardController::class, 'datatable']);

    //notifications
    Route::get('notifications', [NotificationsController::class, 'index']);
    Route::post('notifications/mark-as-read', [NotificationsController::class, 'markAsRead']);
    Route::post('notifications/mark-all-as-read', [NotificationsController::class, 'markAllAsRead']);
});


require __DIR__ . '/api-dash.php';
