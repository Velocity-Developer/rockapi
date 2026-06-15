<?php

use App\Models\FormOrder;

test('form order attributes can be mass assigned', function () {
    $formOrder = new FormOrder([
        'source' => 'website',
        'nama' => 'Budi',
        'hp' => '081234567890',
        'usia' => '30',
        'kebutuhan' => 'Pembuatan website',
    ]);

    expect($formOrder->source)->toBe('website')
        ->and($formOrder->nama)->toBe('Budi')
        ->and($formOrder->hp)->toBe('081234567890')
        ->and($formOrder->usia)->toBe(30)
        ->and($formOrder->kebutuhan)->toBe('Pembuatan website');
});
