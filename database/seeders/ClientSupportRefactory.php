<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CsMainProjectClientSupport;
use App\Models\WebhostClientSupport;
use App\Models\Webhost;
use Exception;

class ClientSupportRefactory extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai proses refactory data dari tb_clientsupport ke CsMainProjectClientSupport dan WebhostClientSupport...');

        try {
            // Ambil data dari tb_clientsupport yang belum diproses (export < 2) atau null
            $today = date('Y-m-d');
            $clientSupportData = DB::table('tb_clientsupport')
                ->where(function ($q) use ($today) {
                    $q->where('export', '<', 2)
                        ->orWhereNull('export')
                        ->orWhereDate('tgl', $today);
                })
                ->limit(500)
                ->get();
            $clientSupportDataCount = $clientSupportData->count();

            $this->command->info('Ditemukan ' . $clientSupportDataCount . ' record yang akan diproses.');

            $processedCount = 0;
            $errorCount = 0;
            $totalCsInserted = 0;
            $totalWebhostInserted = 0;

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

                    $csRecordInserted = 0;
                    $webhostRecordInserted = 0;

                    // Proses setiap kolom layanan untuk CsMainProjectClientSupport
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

                            // CsMainProjectClientSupport::create([
                            //     'cs_main_project_id' => (int) $csMainProjectId,
                            //     'layanan' => $layanan,
                            //     'tanggal' => $data->tgl ?? null,
                            // ]);
                            CsMainProjectClientSupport::updateOrCreate(
                                [
                                    'cs_main_project_id' => (int) $csMainProjectId,
                                    'layanan' => $layanan,
                                    'tanggal' => $data->tgl ?? null
                                ]
                            );

                            $csRecordInserted++;
                            $totalCsInserted++;
                        }
                    }

                    // Proses kolom tanya_jawab untuk WebhostClientSupport
                    $tanyaJawabString = $data->tanya_jawab ?? '';
                    if (!empty($tanyaJawabString)) {
                        // Parse domain dari string tanya_jawab
                        $domains = $this->parseDomains($tanyaJawabString);

                        foreach ($domains as $domain) {
                            // Cari webhost berdasarkan nama_web
                            $webhost = $this->findWebhostByDomain($domain);

                            if ($webhost) {
                                // WebhostClientSupport::create([
                                //     'webhost_id' => $webhost->id_webhost,
                                //     'layanan' => 'tanya_jawab',
                                //     'tanggal' => $data->tgl ?? null,
                                // ]);
                                WebhostClientSupport::updateOrCreate(
                                    [
                                        'webhost_id' => $webhost->id_webhost,
                                        'layanan' => 'tanya_jawab',
                                        'tanggal' => $data->tgl ?? null,
                                    ]
                                );

                                $webhostRecordInserted++;
                                $totalWebhostInserted++;
                            }
                        }
                    }

                    // Update kolom export menjadi 1 untuk menandai sudah diproses
                    DB::table('tb_clientsupport')
                        ->where('id_cs_project', $data->id_cs_project)
                        ->update(['export' => 3]);

                    // Commit transaksi
                    DB::commit();

                    $processedCount++;
                    $this->command->info("{$processedCount}/{$clientSupportDataCount}. Record ID {$data->id_cs_project} berhasil diproses. CS: {$csRecordInserted}, Webhost: {$webhostRecordInserted} records.");
                } catch (Exception $e) {
                    // Rollback transaksi jika terjadi error
                    DB::rollBack();
                    $errorCount++;
                    $this->command->error("Error memproses record ID {$data->id_cs_project}: " . $e->getMessage());
                }
            }

            $this->command->info("Proses selesai!");
            $this->command->info("Total record tb_clientsupport diproses: {$processedCount}");
            $this->command->info("Total record CsMainProjectClientSupport dibuat: {$totalCsInserted}");
            $this->command->info("Total record WebhostClientSupport dibuat: {$totalWebhostInserted}");
            $this->command->info("Total error: {$errorCount}");
        } catch (Exception $e) {
            $this->command->error('Error dalam proses refactory: ' . $e->getMessage());
        }
    }

    /**
     * Parse domain dari string yang bisa dipisahkan dengan koma, spasi, atau enter
     */
    private function parseDomains($domainsString)
    {
        // Pisahkan berdasarkan koma, spasi, atau enter
        $domains = preg_split('/[,\s\n\r]+/', $domainsString, -1, PREG_SPLIT_NO_EMPTY);

        // Normalisasi setiap domain
        $normalizedDomains = [];
        foreach ($domains as $domain) {
            $normalized = $this->normalizeDomain(trim($domain));
            if (!empty($normalized)) {
                $normalizedDomains[] = $normalized;
            }
        }

        return array_unique($normalizedDomains);
    }

    /**
     * Normalisasi domain dengan menghapus http/https, www
     */
    private function normalizeDomain($domain)
    {
        // Hapus protokol http/https
        $domain = preg_replace('/^https?:\/\//', '', $domain);

        // Hapus www
        $domain = preg_replace('/^www\./', '', $domain);

        // Hapus trailing slash
        $domain = rtrim($domain, '/');

        // Hapus path jika ada
        $domain = explode('/', $domain)[0];

        return strtolower(trim($domain));
    }

    /**
     * Cari webhost berdasarkan domain
     */
    private function findWebhostByDomain($domain)
    {
        // Cari exact match dulu
        $webhost = Webhost::where('nama_web', $domain)->first();

        if (!$webhost) {
            // Cari dengan LIKE jika tidak ada exact match
            $webhost = Webhost::where('nama_web', 'like', '%' . $domain . '%')->first();
        }

        if (!$webhost) {
            // Cari dengan normalisasi nama_web dari database
            $webhosts = Webhost::all();
            foreach ($webhosts as $wh) {
                $normalizedNamaWeb = $this->normalizeDomain($wh->nama_web);
                if ($normalizedNamaWeb === $domain) {
                    return $wh;
                }
            }
        }

        return $webhost;
    }
}
