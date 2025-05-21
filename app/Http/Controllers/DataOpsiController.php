<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paket;

class DataOpsiController extends Controller
{

    //gets
    public function gets(Request $request)
    {
        $keys = $request->input('keys');
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return response()->json($result);
    }

    //get
    public function get(string $key)
    {
        $result = [];

        //switch key        
        switch ($key) {
            case 'jenis_project':
                $result = $this->jenis_project();
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

    private function jenis_project()
    {
        return [
            'Lain - Lain',
            'Iklan Google',
            'Deposit Iklan Google',
            'Jasa update iklan google',
            'Pembuatan apk',
            'Pembuatan apk biasa',
            'Pembuatan apk custom',
            'Pembuatan',
            'Perpanjangan',
            'Tambah Space',
            'Pembuatan Tanpa Domain',
            'Pembuatan Tanpa Hosting',
            'Pembuatan Tanpa Domain+Hosting',
            'Jasa Input Produk',
            'Jasa Update Web',
            'Jasa Buat Email',
            'Jasa Ganti Domain',
            'Jasa SEO',
            'Jasa Buat Facebook',
            'Jasa Buat Akun Sosmed',
            'Jasa rating google maps',
            'Jasa buat google maps',
            'Redesign',
            'Jasa Pembuatan Logo',
            'Compro PDF',
            'Lain-lain'
        ];
    }
}
