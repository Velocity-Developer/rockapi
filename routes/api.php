<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\OptionsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\DashboardController;


Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResources([
        'users'         => UsersController::class,
        'roles'         => RolesController::class,
        'permissions'   => PermissionsController::class,
        'posts'         => PostsController::class,
        'terms'         => TermsController::class
    ]);

    Route::put('user/updatepassword/{id}', [UsersController::class, 'updatePassword']);
    Route::put('user/updateavatar/{id}', [UsersController::class, 'updateAvatar']);
    Route::get('option/{key}', [OptionsController::class, 'get']);
    Route::post('setconfig', [ConfigController::class, 'setconfig']);

    //dashboard
    Route::get('dashboard/datatable', [DashboardController::class, 'datatable']);
});

Route::get('config', [ConfigController::class, 'index']);
