<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\WmProject;

class UserWmProject extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $karyawans = [
            'Aditya k' => [
                'id_karyawan' => 80,
                'user_id' => null
            ],
            'Aditya' => [
                'id_karyawan' => 81,
                'user_id' => null
            ],
            'Dita' => [
                'id_karyawan' => 34,
                'user_id' => null
            ],
            'Irawan' => [
                'id_karyawan' => 73,
                'user_id' => null
            ],
            'Lingga' => [
                'id_karyawan' => 75,
                'user_id' => null
            ],
            'Shudqi' => [
                'id_karyawan' => 65,
                'user_id' => null
            ],
            'Dimas' => [
                'id_karyawan' => 67,
                'user_id' => null
            ],
            'Yuda' => [
                'id_karyawan' => 28,
                'user_id' => null
            ],
            'Bima' => [
                'id_karyawan' => 72,
                'user_id' => null
            ],
            'Fajar' => [
                'id_karyawan' => 68,
                'user_id' => null
            ],
            'Galib' => [
                'id_karyawan' => 71,
                'user_id' => null
            ],
            'Reza' => [
                'id_karyawan' => 74,
                'user_id' => null
            ],
            'Joko' => [
                'id_karyawan' => 64,
                'user_id' => null
            ],
            'Anggun' => [
                'id_karyawan' => 76,
                'user_id' => null
            ],
            'Iksan' => [
                'id_karyawan' => 70,
                'user_id' => null
            ],
            'Support' => [
                'id_karyawan' => 11,
                'user_id' => null
            ]
        ];

        //loop karyawans
        foreach ($karyawans as $name => $karyawan) {
            //get user by id_karyawan
            $user = User::where('id_karyawan', $karyawan['id_karyawan'])->first();

            //jika tidak ada, skip
            if (!$user) {
                $this->command->info('Webmaster ' . $name . ' tidak ditemukan');
                continue;
            }

            //update karyawans
            $karyawans[$name]['user_id'] = $user->id;
        }

        //get all WmProject, dengan kolom 'webmaster' isi tapi bukan '-' atau ' '        
        $WmProjects = WmProject::where('webmaster', '!=', null)
            ->where('webmaster', '!=', ' ')
            ->where('webmaster', '!=', '-')
            ->where('user_id', '=', null)
            ->get();

        foreach ($WmProjects as $WmProject) {
            $webmaster = $WmProject->webmaster;

            //jika webmaster tidak ada || kosong || -, skip
            if (!isset($karyawans[$webmaster]) || $webmaster == ' ' || $webmaster == '-') {
                $this->command->info('Webmaster ' . $webmaster . ' tidak ditemukan');
                continue;
            } else {
                $WmProject->update([
                    'user_id' => $karyawans[$webmaster]['user_id'],
                ]);
                $this->command->info('Projek ' . $WmProject->id . ' berhasil diupdate');
            }
        }
    }
}
