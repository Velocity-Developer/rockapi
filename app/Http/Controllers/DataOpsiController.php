<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Paket;
use App\Models\Quality;
use App\Models\User;

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
            if ($result[$key] == 'bank') {
                $result[$key] = $this->get_data($key, $request->kategori ?? '');
            } else {
                $result[$key] = $this->get_data($key);
            }
        }

        return response()->json($result);
    }

    //get
    public function get(string $key, Request $request)
    {
        if ($key == 'bank') {
            $get = $this->get_data($key, $request->kategori ?? '');
        } else {
            $get = $this->get_data($key, $request);
        }

        return response()->json($get);
    }

    //get
    public function get_data(string $key, $request = null)
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
                $result = $this->bank($request);
                break;
            case 'webmaster':
                $result = $this->webmaster();
                break;
            case 'quality':
                $result = $this->quality();
                break;
            case 'users':
                $result = $this->users();
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

    private function bank($kategori = null)
    {
        $data_banks = [
            [
                'value'     => 'bca',
                'label'     => 'BCA',
                'kategori'  => 'umum'
            ],
            [
                'value'     => 'bca stok',
                'label'     => 'BCA Stok',
                'kategori'  => 'umum'
            ],
            [
                'value'     => 'mandiri',
                'label'     => 'Mandiri',
                'kategori'  => 'umum'
            ],
            [
                'value'     => 'bni',
                'label'     => 'BNI',
                'kategori'  => 'umum'
            ],
            [
                'value'     => 'bri',
                'label'     => 'BRI',
                'kategori'  => 'umum'
            ],
            [
                'value'     => 'dbs',
                'label'     => 'DBS',
                'kategori'  => 'umum'
            ],
            [
                'value'     => 'jago',
                'label'     => 'Jago',
                'kategori'  => 'umum'
            ],
            [
                'value'     => 'gopay',
                'label'     => 'Gopay',
                'kategori'  => 'umum'
            ],
            [
                'value'     => 'resellercamp',
                'label'     => 'RESELLERCAMP',
                'kategori'  => 'umum'
            ],
            [
                'value'     => 'srsx',
                'label'     => 'SRSX',
                'kategori'  => 'umum'
            ],
            [
                'value'     => 'jenius',
                'label'     => 'Jenius',
                'kategori'  => 'umum'
            ],
            [
                'value'     => 'bca_vd_1',
                'label'     => 'BCA1 Velocity Developer',
                'kategori'  => 'vd'
            ],
            [
                'value'     => 'bca_vd_2',
                'label'     => 'BCA2 Velocity Developer',
                'kategori'  => 'vd'
            ],
            [
                'value'     => 'mandiri_vd',
                'label'     => 'Mandiri Velocity Developer',
                'kategori'  => 'vd'
            ],
            [
                'value'     => 'bri_vd',
                'label'     => 'BRI Velocity Developer',
                'kategori'  => 'vd'
            ],
            [
                'value'     => 'bni_vd',
                'label'     => 'BNI Velocity Developer',
                'kategori'  => 'vd'
            ],
            [
                'value'     => 'bca_vcm',
                'label'     => 'BCA Velocity Cyber Media',
                'kategori'  => 'vcm'
            ],
            [
                'value'     => 'mandiri_vcm',
                'label'     => 'Mandiri Velocity Cyber Media',
                'kategori'  => 'vcm'
            ],
            [
                'value'     => 'bri_vcm',
                'label'     => 'BRI Velocity Cyber Media',
                'kategori'  => 'vcm'
            ],
            [
                'value'     => 'bni_vcm',
                'label'     => 'BNI Velocity Cyber Media',
                'kategori'  => 'vcm'
            ]
        ];

        //return array by kategori
        $kategori = $kategori ?? 'umum';
        $new_array = [];
        foreach ($data_banks as $item) {
            ///jika katgori != kategori, skip
            if ($item['kategori'] != $kategori) {
                continue;
            }

            $new_array[] = $item;
        }

        return $new_array;
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

    private function users()
    {
        //get all user, status = active
        $users = User::where('status', 'active')->get();
        $result = [];
        foreach ($users as $item) {
            $result[] = [
                'value'     => $item->id,
                'label'     => $item->name,
                'avatar'    => $item->avatar_url
            ];
        }
        return $result;
    }
}
