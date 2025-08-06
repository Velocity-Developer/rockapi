<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Laporan\ProjectProfitController;
use App\Http\Controllers\Laporan\PerpanjangWebJangkaController;
use App\Http\Controllers\Laporan\NetProfitController;
use App\Http\Controllers\Laporan\SiklusLayananController;

Route::middleware(['auth:sanctum'])->group(function () {
    //group laporan
    Route::get('laporan/project_profit', [ProjectProfitController::class, 'index']);
    Route::get('laporan/perpanjang_web_jangka', [PerpanjangWebJangkaController::class, 'index']);
    Route::get('laporan/net_profit', [NetProfitController::class, 'index']);
    Route::get('laporan/siklus_layanan', [SiklusLayananController::class, 'index']);
});
