<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RekapFormController;

Route::middleware(['api_public_verify'])->prefix('public')->group(function () {
    Route::post('rekap-form', [RekapFormController::class, 'store']);
    Route::get('rekap-form/{id}', [RekapFormController::class, 'show']);
    Route::get('rekap-form-konversi-ads', [RekapFormController::class, 'get_konversi_ads']);
    Route::post('rekap-form-update-konversi-ads', [RekapFormController::class, 'update_cek_konversi_ads']);
    Route::get('rekap-form-konversi-nominal-ads', [RekapFormController::class, 'get_konversi_nominal_ads']);
});
