<?php

use App\Http\Controllers\FormOrderController;
use App\Http\Controllers\RekapFormController;
use Illuminate\Support\Facades\Route;

Route::middleware(['allow_any_origin', 'api_public_verify'])->prefix('public')->group(function () {
    Route::options('{any}', fn() => response()->noContent())->where('any', '.*');

    Route::post('rekap-form', [RekapFormController::class, 'store']);
    Route::get('rekap-form/{id}', [RekapFormController::class, 'show']);
    Route::get('rekap-form-konversi-ads', [RekapFormController::class, 'get_konversi_ads']);
    Route::post('rekap-form-update-konversi-ads', [RekapFormController::class, 'update_cek_konversi_ads']);
    Route::get('rekap-form-konversi-nominal-ads', [RekapFormController::class, 'get_konversi_nominal_ads']);
    Route::post('rekap-form-update-konversi-nominal-ads', [RekapFormController::class, 'update_cek_konversi_nominal_ads']);
    Route::post('rekap-form-update-failed', [RekapFormController::class, 'update_failed']);

    Route::post('form-order', [FormOrderController::class, 'public_store']);
});
