<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class KaryawanUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // array karyawan and role
        $karyawans_roles = [
            'aditya' => [
                'role' => 'webdeveloper',
                'status' => 'active',
            ],
            'adityak' => [
                'role' => 'webdeveloper',
                'status' => 'active',
            ],
            'putri' => [
                'role' => 'finance',
                'status' => 'active',
            ],
            'rosa' => [
                'role' => 'advertising',
                'status' => 'active',
            ],
            'dini' => [
                'role' => 'advertising',
                'status' => 'active',
            ],
            'anggun' => [
                'role' => 'advertising_internal',
                'status' => 'active',
            ],
            'lingga' => [
                'role' => 'webdeveloper',
                'status' => 'active',
            ],
            'reza' => [
                'role' => 'revisi',
                'status' => 'active',
            ],
            'irawan' => [
                'role' => 'webdeveloper',
                'status' => 'active',
            ],
            'bima' => [
                'role' => 'finance',
                'status' => 'active',
            ],
            'galib' => [
                'role' => 'customer_service',
                'status' => 'active',
            ],
            'iksan' => [
                'role' => 'customer_service',
                'status' => 'active',
            ],
            'sofyan' => [
                'role' => 'advertising',
                'status' => 'active',
            ],
            'fajar' => [
                'role' => 'support',
                'status' => 'active',
            ],
            'dimas' => [
                'role' => 'webdeveloper',
                'status' => 'deactive',
            ],
            'shudqi' => [
                'role' => 'webdeveloper',
                'status' => 'active',
            ],
            'joko' => [
                'role' => 'webdeveloper',
                'status' => 'deactive',
            ],
            'ayu' => [
                'role' => 'advertising',
                'status' => 'active',
            ],
            'vicky' => [
                'role' => 'customer_service',
                'status' => 'active',
            ],
            'yoga' => [
                'role' => 'customer_service',
                'status' => 'active',
            ],
            'kendra' => [
                'role' => 'manager_project',
                'status' => 'active',
            ],
            'aji' => [
                'role' => 'manager_project',
                'status' => 'deactive',
            ],
            'rahmad' => [
                'role' => 'customer_service',
                'status' => 'deactive',
            ],
            'rika' => [
                'role' => 'customer_service',
                'status' => 'deactive',
            ],
            'siti' => [
                'role' => 'customer_service',
                'status' => 'deactive',
            ],
            'muh' => [
                'role' => 'customer_service',
                'status' => 'active',
            ],
            'dita' => [
                'role' => 'customer_service',
                'status' => 'active',
            ],
            'agus' => [
                'role' => 'support',
                'status' => 'active',
            ],
            'yuda' => [
                'role' => 'revisi',
                'status' => 'active',
            ],
            'agha' => [
                'role' => 'user',
                'status' => 'deactive',
            ],
            'billing' => [
                'role' => 'customer_service',
                'status' => 'active',
            ],
            'toro' => [
                'role' => 'owner',
                'status' => 'active',
            ],
            'eko' => [
                'role' => 'manager_project',
                'status' => 'active',
            ],
            'super' => [
                'role' => 'admin',
                'status' => 'active',
            ],
            'pemilik' => [
                'role' => 'owner',
                'status' => 'active',
            ],
            'pm' => [
                'role' => 'manager_project',
                'status' => 'active',
            ],
            'budi' => [
                'role' => 'budi',
                'status' => 'active',
            ],
            'webcustom' => [
                'role' => 'webdeveloper',
                'status' => 'active',
            ],
            'support' => [
                'role' => 'support',
                'status' => 'active',
            ],
            'webbiasa' => [
                'role' => 'webdeveloper',
                'status' => 'active',
            ],

        ];

        // get all Karyawan
        $karyawans = \App\Models\Karyawan::all();

        // command info progress
        $this->command->info('Run KaryawanUserSeeder for '.count($karyawans).' Karyawan');

        // create user by looping Karyawan
        $counter = 1;
        foreach ($karyawans as $karyawan) {

            $username = $karyawan->username ? Str::lower($karyawan->username) : Str::lower($karyawan->nama);
            $email = $karyawan->email;

            if ($username == 'billing' || $email == 0 || $email == 1 || ! $email || $email == '' || $email == 'a' || $email == '-') {
                $email = $username.'@example.com';
            }

            // check user dengan username
            if (\App\Models\User::where('username', $username)->exists()) {
                $this->command->info('User '.$username.' sudah ada');
                // update user
                $user = \App\Models\User::where('username', $username)->first();
                $user->update([
                    'username' => $username,
                    'id_karyawan' => $karyawan->id_karyawan,
                    'status' => $karyawans_roles[$username] ? $karyawans_roles[$username]['status'] : 'active',
                ]);

                continue;
            }

            // create user
            $user = \App\Models\User::create([
                'name' => $karyawan->nama,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Hash::make('XfF!8%tnEAi$isOw61ND'),
                'remember_token' => Str::random(10),
                'hp' => $karyawan->hp,
                'alamat' => $karyawan->alamat,
                'tgl_masuk' => $karyawan->tgl_masuk,
                'username' => $username,
                'id_karyawan' => $karyawan->id_karyawan,
                'status' => $karyawans_roles[$username] ? $karyawans_roles[$username]['status'] : 'active',
            ]);

            // assign role
            if (isset($karyawans_roles[$username])) {
                $role = $karyawans_roles[$username]['role'] ?? 'user';
            } else {
                $role = 'user';
            }
            $user->assignRole($role);

            // command info
            $this->command->info(count($karyawans).' / '.$counter.', Karyawan '.$username.' dengan role '.$role.' berhasil dibuat');

            $counter++;
        }
    }
}
