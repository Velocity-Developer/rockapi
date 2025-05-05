<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class BankCsMainProjectSeeder extends Seeder
{
    /**
     * Seeder untuk membuat PIVOT relasi antara model 'Bank' dan 'CsMainProject'
     */
    public function run(): void
    {
        //ambil semua data dari model 'Bank'
        $banks = DB::table('tb_bank')->get();

        //command info progress
        $this->command->info('Run BankCsMainProjectSeeder');

        $counter = 0;

        //loop
        foreach ($banks as $bank) {

            $message = '';

            // Skip kalau kosong
            if (!$bank->jenis) {

                //message info
                $message = 'Bank jenis is empty for id: ' . $bank->id;

                //command info progress
                $this->command->info('Pivot Bank Jenis : ' . $counter . ' / ' . count($banks) . ', ' . $message);
                $counter++;

                continue;
            }

            //unserialize jenis
            $items = unserialize($bank->jenis);

            //jika kosong atau bukan array, SKIP
            if (empty($items) || !is_array($items)) {

                //message info
                $message = 'Bank jenis is not an array for id: ' . $bank->id;

                //command info progress
                $this->command->info('Pivot Bank Jenis : ' . $counter . ' / ' . count($banks) . ', ' . $message);
                $counter++;

                continue;
            }

            //loop item jenis
            foreach ($items as $item) {
                //explode
                $jns = $item ? explode('-', $item) : '';

                // Cek dulu apakah data pivot sudah ada
                $exists = DB::table('bank_cs_main_project')
                    ->where('bank_id', $bank->id)
                    ->where('cs_main_project_id', $jns[1])
                    ->exists();

                if (!$exists) {
                    // Kalau belum ada, baru insert
                    DB::table('bank_cs_main_project')->insert([
                        'bank_id' => $bank->id,
                        'cs_main_project_id' => $jns[1],
                        'tipe' => $jns[0],
                    ]);

                    //message info
                    $message = 'Bank id: ' . $bank->id . ' Pivot created';
                } else {
                    //message info
                    $message = 'Bank id: ' . $bank->id . ' Pivot already exists';
                }
            }

            //command info progress
            $this->command->info('Pivot Bank Jenis : ' . $counter . ' / ' . count($banks) . ', ' . $message);

            $counter++;
        }
    }
}
