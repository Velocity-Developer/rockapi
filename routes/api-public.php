<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RekapFormController;

Route::middleware(['api_public_verify'])->prefix('public')->group(function () {
    Route::post('rekap-form', [RekapFormController::class, 'store']);
    Route::get('rekap-form/konversi-ads', [RekapFormController::class, 'get_konversi_ads']);
});
