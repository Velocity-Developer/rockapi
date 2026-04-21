<?php

use App\Http\Controllers\Laporan\NetProfitController;
use App\Http\Controllers\Laporan\PerpanjangWebJangkaController;
use App\Http\Controllers\Laporan\ProjectProfitController;
use App\Http\Controllers\Laporan\KlienPerpanjangController;
use App\Http\Controllers\Laporan\OrderKumulatifController;
use App\Http\Controllers\Laporan\PembuatanController;
use App\Http\Controllers\Laporan\LeadAmController;
use App\Http\Controllers\Laporan\RincianTransaksiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // group laporan
    Route::get('laporan/project_profit', [ProjectProfitController::class, 'index']);
    Route::get('laporan/perpanjang_web_jangka', [PerpanjangWebJangkaController::class, 'index']);
    Route::get('laporan/net_profit', [NetProfitController::class, 'index']);
    Route::get('laporan/net_profit_perpanjangan', [NetProfitController::class, 'perpanjangan']);
    Route::get('laporan/order_kumulatif', [OrderKumulatifController::class, 'index']);
    Route::get('laporan/klien_perpanjang', [KlienPerpanjangController::class, 'index']);
    Route::get('laporan/klien_perpanjang_expired_whmcs', [KlienPerpanjangController::class, 'expiredWhmcs']);
    Route::get('laporan/klien_perpanjang_grafik', [KlienPerpanjangController::class, 'grafik']);
    Route::get('laporan/klien_perpanjang_grafik_data', [KlienPerpanjangController::class, 'grafikData']);
    Route::get('laporan/pembuatan_bulanan', [PembuatanController::class, 'bulanan']);
    Route::get('laporan/lead_am', [LeadAmController::class, 'index']);
    Route::put('laporan/lead_am/{project}/staff', [LeadAmController::class, 'updateStaff']);
    Route::get('laporan/rincian_transaksi', [RincianTransaksiController::class, 'index']);
});
