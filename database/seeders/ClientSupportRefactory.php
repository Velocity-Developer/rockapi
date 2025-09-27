<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CsMainProjectClientSupport;
use Exception;

class ClientSupportRefactory extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai proses refactory data dari tb_clientsupport ke CsMainProjectClientSupport...');

        try {
            // Ambil data dari tb_clientsupport yang belum diproses (export != 1)
            $clientSupportData = DB::table('tb_clientsupport')
                ->where('export', '!=', 1)
                ->orWhereNull('export')
                ->get();

            $this->command->info('Ditemukan ' . $clientSupportData->count() . ' record yang akan diproses.');

            $processedCount = 0;
            $errorCount = 0;
            $totalInserted = 0;

            // Kolom yang berisi kumpulan ID yang dipisahkan koma
            $layananColumns = [
                'revisi_1',
                'perbaikan_revisi_1',
                'revisi_2',
                'perbaikan_revisi_2',
                'update_web'
            ];

            foreach ($clientSupportData as $data) {
                try {
                    // Mulai transaksi untuk setiap record
                    DB::beginTransaction();

                    $recordInserted = 0;

                    // Proses setiap kolom layanan
                    foreach ($layananColumns as $layanan) {
                        $idsString = $data->$layanan ?? '';

                        // Skip jika kolom kosong
                        if (empty($idsString)) {
                            continue;
                        }

                        // Pecah string ID yang dipisahkan koma
                        $ids = array_filter(array_map('trim', explode(',', $idsString)));

                        // Buat record untuk setiap ID
                        foreach ($ids as $csMainProjectId) {
                            // Validasi ID adalah numeric
                            if (!is_numeric($csMainProjectId)) {
                                continue;
                            }

                            //jika nilai = int 0, maka skip
                            if ((int) $csMainProjectId === 0) {
                                continue;
                            }

                            CsMainProjectClientSupport::create([
                                'cs_main_project_id' => (int) $csMainProjectId,
                                'layanan' => $layanan,
                                'tanggal' => $data->tgl ? date('Y-m-d', strtotime($data->tgl)) . ' 00:00:00' : null,
                            ]);

                            $recordInserted++;
                            $totalInserted++;
                        }
                    }

                    // Update kolom export menjadi 1 untuk menandai sudah diproses
                    DB::table('tb_clientsupport')
                        ->where('id', $data->id)
                        ->update(['export' => 1]);

                    // Commit transaksi
                    DB::commit();

                    $processedCount++;
                    $this->command->info("Record ID {$data->id} berhasil diproses. Inserted {$recordInserted} records.");
                } catch (Exception $e) {
                    // Rollback transaksi jika terjadi error
                    DB::rollBack();
                    $errorCount++;
                    $this->command->error("Error memproses record ID {$data->id}: " . $e->getMessage());
                }
            }

            $this->command->info("Proses selesai!");
            $this->command->info("Total record tb_clientsupport diproses: {$processedCount}");
            $this->command->info("Total record CsMainProjectClientSupport dibuat: {$totalInserted}");
            $this->command->info("Total error: {$errorCount}");
        } catch (Exception $e) {
            $this->command->error('Error dalam proses refactory: ' . $e->getMessage());
        }
    }
}
