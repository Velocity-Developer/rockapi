<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Paket;
use App\Models\Quality;
use Illuminate\Support\Facades\App;

class DataOpsiController extends Controller
{

    //gets
    public function gets(Request $request)
    {
        $keys = $request->input('keys');
        //jika kosong, return empty array

        if ($keys == null || !is_array($keys)) {
            return response()->json([]);
        }

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get_data($key);
        }

        return response()->json($result);
    }

    //get
    public function get(string $key)
    {
        return $this->get_data($key);
    }

    //get
    public function get_data(string $key)
    {
        $result = [];

        //switch key        
        switch ($key) {
            case 'jenis_project':
                $result = $this->jenis_project();
                break;
            case 'karyawan':
                $result = $this->karyawan();
                break;
            case 'paket':
                $result = $this->paket();
                break;
            case 'bank':
                $result = $this->bank();
                break;
            case 'webmaster':
                $result = $this->webmaster();
                break;
            case 'quality':
                $result = $this->quality();
                break;
            default:
                $result = [];
        }

        return $result;
    }

    private function paket()
    {
        $paket = Paket::all();
        $result = [];
        foreach ($paket as $item) {
            $result[] = [
                'value' => $item->id_paket,
                'label' => $item->paket
            ];
        }
        return $result;
    }

    private function karyawan()
    {
        //get all karyawan
        $karyawan = Karyawan::all();
        $result = [];
        foreach ($karyawan as $item) {
            $result[] = [
                'value' => $item->id_karyawan,
                'label' => $item->nama
            ];
        }
        return $result;
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

    private function webmaster()
    {
        $result = [
            [
                'value' => 'Irawan',
                'label' => 'Irawan'
            ],
            [
                'value' => 'Dita',
                'label' => 'Dita'
            ],
            [
                'value' => 'Aditya k',
                'label' => 'Aditya k'
            ],
            [
                'value' => 'Aditya',
                'label' => 'Aditya'
            ],
            [
                'value' => 'Lingga',
                'label' => 'Lingga'
            ],
            [
                'value' => 'Shudqi',
                'label' => 'Shudqi'
            ]
        ];
        return $result;
    }

    private function quality()
    {
        //get all Quality
        $Quality = Quality::all();
        $result = [];
        foreach ($Quality as $item) {
            $result[] = [
                'value' => $item->detail,
                'label' => $item->detail
            ];
        }
        return $result;
    }
}
