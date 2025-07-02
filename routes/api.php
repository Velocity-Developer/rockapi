<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\DataOpsiController;
use App\Http\Controllers\WebhostController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BillDataWebController;
use App\Http\Controllers\TransaksiIklanGoogleController;
use App\Http\Controllers\JenisBlmTerpilihController;
use App\Http\Controllers\BankTransaksiController;
use App\Http\Controllers\SaldoBankController;
use App\Http\Controllers\CsMainProjectController;
use App\Http\Controllers\WmProjectController;
use App\Http\Controllers\ProjectListController;
use App\Http\Controllers\LaporanNilaiController;
use App\Http\Controllers\ProjectManagerController;
use App\Http\Controllers\Laporan\ProjectProfitController as LaporanProjectProfitController;

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
        'posts'             => PostsController::class,
        'terms'             => TermsController::class,
        'webhost'           => WebhostController::class,
        'bank_transaksi'    => BankTransaksiController::class,
        'saldo_bank'        => SaldoBankController::class,
        'cs_main_project'   => CsMainProjectController::class,
        'wm_project'        => WmProjectController::class,
    ]);

    //data_opsi
    Route::get('data_opsi/{key}', [DataOpsiController::class, 'get']);
    Route::get('data_opsis', [DataOpsiController::class, 'gets']);

    //search webhost
    Route::get('webhost_search/{keyword}', [WebhostController::class, 'search']);

    //billing
    Route::get('billing', [BillingController::class, 'index']);
    Route::get('billing_prediksi_bulanini', [BillingController::class, 'prediksi_bulanini']);

    //bill_dataweb
    Route::get('bill_dataweb', [BillDataWebController::class, 'index']);

    //transaksi_iklan_google
    Route::get('transaksi_iklan_google', [TransaksiIklanGoogleController::class, 'index']);
    //jenis_blm_terpilih
    Route::get('jenis_blm_terpilih', [JenisBlmTerpilihController::class, 'index']);

    //bank_transaksi/search_jenis
    Route::get('bank_transaksi_search_jenis/{keyword}', [BankTransaksiController::class, 'search_jenis']);
    //bank_transaksi_last_transaksi
    Route::get('bank_transaksi_last_transaksi', [BankTransaksiController::class, 'get_last_transaksi']);

    //bank_transaksi_export
    Route::get('bank_transaksi_export', [BankTransaksiController::class, 'export']);

    //project_list
    Route::get('project_list', [ProjectListController::class, 'index']);

    //laporan_nilai
    Route::get('laporan_nilai', [LaporanNilaiController::class, 'index']);

    //project_manager
    Route::get('project_manager', [ProjectManagerController::class, 'index']);

    //group laporan
    Route::get('laporan/project_profit', [LaporanProjectProfitController::class, 'index']);
});

require __DIR__ . '/api-dash.php';
