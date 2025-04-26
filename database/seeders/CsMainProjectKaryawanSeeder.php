<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CsMainProjectKaryawanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua project
        $projects = DB::table('tb_cs_main_project')->get();

        foreach ($projects as $project) {

            if (!$project->dikerjakan_oleh) {
                continue; // Skip kalau kosong
            }

            // Pecah isi berdasarkan koma
            $entries = array_filter(explode(',', $project->dikerjakan_oleh));

            //skip kalau kosong
            if (empty($entries)) {
                continue;
            }

            foreach ($entries as $entry) {

                // Cari ID karyawan dan porsi
                if (preg_match('/(\d+)\[(\d+)\]/', $entry, $matches)) {
                    $karyawanId = (int) $matches[1];
                    $porsi = (int) $matches[2];

                    // Cek dulu apakah data pivot sudah ada
                    $exists = DB::table('cs_main_project_karyawan')
                        ->where('cs_main_project_id', $project->id)
                        ->where('karyawan_id', $karyawanId)
                        ->exists();

                    if (!$exists) {
                        // Kalau belum ada, baru insert
                        DB::table('cs_main_project_karyawan')->insert([
                            'cs_main_project_id' => $project->id,
                            'karyawan_id' => $karyawanId,
                            'porsi' => $porsi,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    //command info
                    $this->command->info("Berhasil pivot project {$project->id} dan karyawan {$karyawanId} dengan porsi {$porsi}");
                }
            }
        }
    }
}
