<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class BankRelasiJenisSeeder extends Seeder
{
    /**
     * Seeder untuk membuat PIVOT relasi antara model 'Bank' dan 'CsMainProject'
     */
    public function run(): void
    {

        //ambil data bank yang belum memiliki relasi di bank_transaksi_keluar atau bank_cs_main_project
        $banks = DB::table('tb_bank')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('bank_transaksi_keluar')
                    ->whereColumn('bank_transaksi_keluar.bank_id', 'tb_bank.id');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('bank_cs_main_project')
                    ->whereColumn('bank_cs_main_project.bank_id', 'tb_bank.id');
            })
            ->get();

        //command info progress
        $this->command->info('Run BankRelasiJenisSeeder');
        $this->command->info('Jumlah bank yang akan diproses: ' . $banks->count());

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

                //jika $jns[0] = keluar, maka proses di 'bank_transaksi_keluar'
                if ($jns[0] == 'keluar') {

                    // Cek dulu apakah data pivot sudah ada
                    $exists = DB::table('bank_transaksi_keluar')
                        ->where('bank_id', $bank->id)
                        ->where('transaksi_keluar_id', $jns[1])
                        ->exists();

                    if (!$exists) {
                        // Kalau belum ada, baru insert
                        DB::table('bank_transaksi_keluar')->insert([
                            'bank_id' => $bank->id,
                            'transaksi_keluar_id' => $jns[1],
                        ]);
                        //message info
                        $message = 'Bank id: ' . $bank->id . ' & transaksi_keluar_id: ' . $jns[1] . ', Pivot created';
                    } else {
                        //message info
                        $message = 'Bank id: ' . $bank->id . ' & transaksi_keluar_id: ' . $jns[1] . ', Pivot already exists';
                    }
                } else {
                    //jika $jns[0] = masuk, maka proses di 'bank_cs_main_project'

                    // Cek dulu apakah data pivot sudah ada
                    $exists = DB::table('bank_cs_main_project')
                        ->where('bank_id', $bank->id)
                        ->where('cs_main_project_id', $jns[1])
                        ->exists();

                    if (!$exists) {
                        // Kalau belum ada, baru insert
                        DB::table('bank_cs_main_project')->insert([
                            'bank_id' => $bank->id,
                            'cs_main_project_id' => $jns[1]
                        ]);

                        //message info
                        $message = 'Bank id: ' . $bank->id . ' & cs_main_project_id: ' . $jns[1] . ', Pivot created';
                    } else {
                        //message info
                        $message = 'Bank id: ' . $bank->id . ' & cs_main_project_id: ' . $jns[1] . ', Pivot already exists';
                    }
                }
            }

            //command info progress
            $this->command->info('Pivot Bank Jenis : ' . $counter . ' / ' . count($banks) . ', ' . $message);

            $counter++;
        }
    }
}
