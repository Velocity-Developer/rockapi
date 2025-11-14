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
use App\Http\Controllers\CheckPaketController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ServerPackageController;
use App\Http\Controllers\ServerUserController;
use App\Http\Controllers\JournalCategoryController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\TodoCategoryController;
use App\Http\Controllers\NotificationDebugController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\ClientSupportController;

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
        'posts'                 => PostsController::class,
        'terms'                 => TermsController::class,
        'webhost'               => WebhostController::class,
        'bank_transaksi'        => BankTransaksiController::class,
        'saldo_bank'            => SaldoBankController::class,
        'cs_main_project'       => CsMainProjectController::class,
        'wm_project'            => WmProjectController::class,
        'servers'               => ServerController::class,
        'server_packages'       => ServerPackageController::class,
        'server_users'          => ServerUserController::class,
        'journal_category'      => JournalCategoryController::class,
        'journal'               => JournalController::class,
        'invoice'               => InvoiceController::class,
        'customer'              => CustomerController::class,
        'todo_category'       => TodoCategoryController::class,
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
    Route::post('project_manager_save', [ProjectManagerController::class, 'save']);

    //client_support
    Route::get('client_support', [ClientSupportController::class, 'index']);
    Route::post('client_support/store', [ClientSupportController::class, 'store']);
    Route::get('client_support_by_tgl/{tanggal}', [ClientSupportController::class, 'by_tanggal']);

    //lain_lain
    Route::get('/check_paket', CheckPaketController::class);

    //server
    Route::get('/servers_sync_packages/{id}', [ServerController::class, 'sync_packages']);
    Route::get('/servers_sync_package_detail/{idpackage}', [ServerController::class, 'sync_packageDetail']);
    Route::get('/servers_sync_users/{id}', [ServerController::class, 'sync_users']);
    Route::get('/servers_sync_user_detail/{iduserserver}', [ServerController::class, 'sync_userDetail']);
    //search cs_main_project
    Route::get('cs_main_project_search/{keyword}', [CsMainProjectController::class, 'search']);
    Route::get('cs_main_project_lastdata', [CsMainProjectController::class, 'lastdata']);

    // User search route
    Route::get('/user_search/{keyword}', [UsersController::class, 'search']);

    // Todo routes - Manual definition to control route order
    Route::prefix('todos')->group(function () {
        // Collection routes without parameters
        Route::get('/', [TodoController::class, 'index']); // List todos
        Route::post('/', [TodoController::class, 'store']); // Create todo

        // Update todo status
        Route::put('update-status/{id}', [TodoController::class, 'updateStatus']); // Update todo status

        // Special routes without parameters
        Route::get('/my', [TodoController::class, 'myTodos']); // Get todos assigned to current user
        Route::get('/created', [TodoController::class, 'createdTodos']); // Get todos created by current user
        Route::get('/statistics', [TodoController::class, 'statistics']); // Get todo statistics

        // Routes with todo ID parameter
        Route::get('/{id}', [TodoController::class, 'show']); // Get single todo
        Route::put('/{id}', [TodoController::class, 'update']); // Update todo
        Route::delete('/{id}', [TodoController::class, 'destroy']); // Delete todo

        // Assignment management routes
        Route::post('/{id}/assign', [TodoController::class, 'assign']); // Assign todo to users/roles
        Route::get('/{id}/assignments', [TodoController::class, 'assignments']); // Get all assignments for a todo
        Route::put('/{id}/assignments/{assignmentId}/status', [TodoController::class, 'updateAssignmentStatus']); // Update assignment status
        Route::post('/{id}/claim', [TodoController::class, 'claim']); // Claim a public todo
    });

    // Todo Category routes
    Route::prefix('todo_category')->group(function () {
        Route::get('/active', [TodoCategoryController::class, 'active']); // Get active categories
    });

    // Debug routes for notification troubleshooting
    Route::prefix('debug')->group(function () {
        Route::get('/notifications', [NotificationDebugController::class, 'testNotificationCreation']);
    });
});

// Invoice PDF route
Route::get('/invoice/{id}/pdf', [InvoiceController::class, 'printPdf']);

// Telegram routes
Route::prefix('telegram')->group(function () {
    Route::post('/webhook', [TelegramController::class, 'webhook']);
    Route::get('/status', [TelegramController::class, 'status']);
});

require __DIR__ . '/api-dash.php';
require __DIR__ . '/api-laporan.php';
