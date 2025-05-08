<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DataOpsiController extends Controller
{
    //get
    public function get(string $key)
    {
        $result = [];

        //switch key        
        switch ($key) {
            case 'jenis':
                $result = $this->jenis();
                break;
            case 'bank':
                $result = $this->bank();
                break;
            default:
                $result = [];
        }

        return response()->json($result);
    }

    private function bank()
    {
        return [
            [
                'value' => 'bca',
                'label' => 'BCA'
            ],
            [
                'value' => 'bca stok',
                'label' => 'BCA Stok'
            ],
            [
                'value' => 'mandiri',
                'label' => 'Mandiri'
            ],
            [
                'value' => 'bni',
                'label' => 'BNI'
            ],
            [
                'value' => 'bri',
                'label' => 'BRI'
            ],
            [
                'value' => 'dbs',
                'label' => 'DBS'
            ],
            [
                'value' => 'jago',
                'label' => 'Jago'
            ],
            [
                'value' => 'gopay',
                'label' => 'Gopay'
            ],
            [
                'value' => 'resellercamp',
                'label' => 'RESELLERCAMP'
            ],
            [
                'value' => 'srsx',
                'label' => 'SRSX'
            ],
            [
                'value' => 'jenius',
                'label' => 'Jenius'
            ]
        ];
    }
}
