<?php

use App\Http\Controllers\FormOrderController;
use App\Models\FormOrder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(Tests\TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
    ]);

    DB::purge('sqlite');
    DB::reconnect('sqlite');

    Schema::create('form_orders', function (Blueprint $table) {
        $table->id();
        $table->string('source');
        $table->string('nama');
        $table->string('hp');
        $table->unsignedTinyInteger('usia')->nullable();
        $table->text('kebutuhan');
        $table->timestamps();
    });
});

test('form order controller handles crud operations', function () {
    $controller = new FormOrderController;

    $storeResponse = $controller->store(Request::create('/form-orders', 'POST', [
        'source' => 'website',
        'nama' => 'Budi',
        'hp' => '081234567890',
        'usia' => 30,
        'kebutuhan' => 'Pembuatan website',
    ]));

    expect($storeResponse->getStatusCode())->toBe(201);

    $formOrder = FormOrder::firstOrFail();
    $controller->update(Request::create("/form-orders/{$formOrder->id}", 'PATCH', [
        'usia' => 31,
        'kebutuhan' => 'Pembuatan toko online',
    ]), (string) $formOrder->id);

    expect($formOrder->fresh()->usia)->toBe(31)
        ->and($formOrder->fresh()->kebutuhan)->toBe('Pembuatan toko online');

    $indexResponse = $controller->index(Request::create('/form-orders', 'GET', [
        'q' => 'Budi',
        'source' => 'website',
    ]));
    $indexData = $indexResponse->getData(true);

    expect($indexData['total'])->toBe(1);

    $showResponse = $controller->show((string) $formOrder->id);

    expect($showResponse->getData(true)['nama'])->toBe('Budi');

    $destroyResponse = $controller->destroy((string) $formOrder->id);

    expect($destroyResponse->getStatusCode())->toBe(200)
        ->and(FormOrder::count())->toBe(0);
});

test('form order controller stores order without usia', function () {
    $controller = new FormOrderController;

    $response = $controller->store(Request::create('/form-orders', 'POST', [
        'source' => 'website',
        'nama' => 'Budi',
        'hp' => '081234567890',
        'kebutuhan' => 'Pembuatan website',
    ]));

    $formOrder = FormOrder::firstOrFail();

    expect($response->getStatusCode())->toBe(201)
        ->and($formOrder->usia)->toBeNull();
});
