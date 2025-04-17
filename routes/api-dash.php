<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\Dash\MediaController;
use App\Http\Controllers\Dash\ConfigController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResources([
        'users'         => UsersController::class,
        'dash/media'    => MediaController::class
    ]);

    Route::put('user/updatepassword/{id}', [UsersController::class, 'updatePassword']);
    Route::put('user/updateavatar/{id}', [UsersController::class, 'updateAvatar']);
    Route::post('dash/setconfig', [ConfigController::class, 'setconfig']);
});

Route::get('dash/config', [ConfigController::class, 'index']);
