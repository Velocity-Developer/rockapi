<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class KaryawanUserSeeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        //array karyawan and role
        $karyawans_roles = [
            'aditya'    => 'webdeveloper',
            'adityak'   => 'webdeveloper',
            'putri'     => 'finance',
            'rosa'      => 'advertising',
            'dini'      => 'advertising',
            'anggun'    => 'advertising',
            'lingga'    => 'webdeveloper',
            'reza'      => 'revisi',
            'irawan'    => 'webdeveloper',
            'bima'      => 'finance',
            'galib'     => 'customer_service',
            'iksan'     => 'customer_service',
            'sofyan'    => 'advertising',
            'fajar'     => 'support',
            'dimas'     => 'webdeveloper',
            'shudqi'    => 'webdeveloper',
            'joko'      => 'user',
            'ayu'       => 'advertising',
            'vicky'     => 'customer_service',
            'yoga'      => 'customer_service',
            'kendra'    => 'manager_project',
            'aji'       => 'manager_project',
            'rahmad'    => 'customer_service',
            'rika'      => 'customer_service',
            'siti'      => 'customer_service',
            'muh'       => 'manager_advertising',
            'agus'      => 'support',
            'yuda'      => 'revisi',
            'agha'      => 'user',
            'billing'   => 'customer_service',
            'toro'      => 'owner',
            'eko'       => 'customer_service',
            'super'     => 'admin',
            'pemilik'   => 'owner',
            'pm'        => 'manager_project',
            'webcustom' => 'webdeveloper',
            'support'   => 'support',
            'webbiasa'  => 'webdeveloper',
        ];

        //get all Karyawan
        $karyawans = \App\Models\Karyawan::all();

        //command info progress
        $this->command->info('Run KaryawanUserSeeeder for ' . count($karyawans) . ' Karyawan');

        //create user by looping Karyawan
        $counter = 1;
        foreach ($karyawans as $karyawan) {

            $username   = $karyawan->username ?? Str::lower($karyawan->username);
            $email      = $karyawan->email;

            if ($email == 0 || $email == 1 || !$email || $email == '' || $email == 'a' || $email == '-') {
                $email = $username . '@example.com';
            }

            //create user
            $user = \App\Models\User::create([
                'name'              => $karyawan->nama,
                'email'             => $email,
                'email_verified_at' => now(),
                'password'          => Hash::make('XfF!8%tnEAi$isOw61ND'),
                'remember_token'    => Str::random(10),
                'hp'                => $karyawan->hp,
                'alamat'            => $karyawan->alamat,
                'tgl_masuk'         => $karyawan->tgl_masuk,
            ]);

            //assign role
            if (isset($karyawans_roles[$username])) {
                $user->assignRole($karyawans_roles[$username]);
            } else {
                $user->assignRole('user');
            }

            //command info
            $this->command->info(count($karyawans) . ' / ' . $counter . ', Karyawan ' . $username . ' berhasil ');

            $counter++;
        }
    }
}
