<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dash\MediaController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('dash/media', MediaController::class);
});
