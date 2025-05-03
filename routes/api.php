<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\WebhostController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BillDataWebController;
use App\Http\Controllers\TransaksiIklanGoogleController;
use App\Http\Controllers\JenisBlmTerpilihController;

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
        'posts'         => PostsController::class,
        'terms'         => TermsController::class,
        'webhost'       => WebhostController::class
    ]);

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
});

require __DIR__ . '/api-dash.php';
