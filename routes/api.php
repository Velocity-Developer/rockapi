<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\BillingController;

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
        'terms'         => TermsController::class
    ]);

    //billing
    Route::get('billing', [BillingController::class, 'index']);
    Route::get('billing_prediksi_bulanini', [BillingController::class, 'prediksi_bulanini']);
});

require __DIR__ . '/api-dash.php';
