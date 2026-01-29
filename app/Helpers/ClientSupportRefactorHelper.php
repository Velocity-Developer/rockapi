<?php

namespace App\Helpers;

use App\Models\CsMainProjectClientSupport;
use App\Models\Webhost;
use App\Models\WebhostClientSupport;
use Illuminate\Support\Facades\DB;

/*
Helper class untuk refactor data
dari tb_clientsupport ke CsMainProjectClientSupport dan WebhostClientSupport
*/

class ClientSupportRefactorHelper
{
    public static function refactor($tanggal)
    {
        $instance = new static; // late binding

        $result = [];
        $message = '';

        // pastikan $tanggal Y-m-d, jika bukan ubah ke Y-m-d
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $tanggal = date('Y-m-d', strtotime($tanggal));
        }
        $result['tanggal'] = $tanggal;

        // Kolom yang berisi kumpulan ID yang dipisahkan koma
        $layananColumns = [
            'revisi_1',
            'perbaikan_revisi_1',
            'revisi_2',
            'perbaikan_revisi_2',
            'update_web',
        ];

        // get data dari tabel 'tb_clientsupport' berdasarkan $tanggal
        $clientSupportData = DB::table('tb_clientsupport')
            ->where('tgl', $tanggal)
            ->first();

        // jika tidak ada data, return
        if (! $clientSupportData) {
            $message .= 'Tidak ada data untuk tanggal '.$tanggal;
        }

        // Proses setiap kolom layanan untuk CsMainProjectClientSupport
        foreach ($layananColumns as $layanan) {
            // jika layanan tidak ada, continue
            if (empty($clientSupportData->$layanan)) {
                continue;
            }

            $idsString = $clientSupportData->$layanan ?? '';
            // Skip jika kolom kosong
            if (empty($idsString)) {
                continue;
            }

            // Pecah string ID yang dipisahkan koma
            $cs_main_project_ids = array_filter(array_map('trim', explode(',', $idsString)));

            // Buat record untuk setiap ID
            foreach ($cs_main_project_ids as $id) {

                // id bukan 0
                if ($id == 0) {
                    continue;
                }

                // Cek apakah data sudah ada di tabel
                $existingRecord = CsMainProjectClientSupport::where('cs_main_project_id', $id)
                    ->where('layanan', $layanan)
                    ->where('tanggal', $tanggal)
                    ->first();

                if (! $existingRecord) {
                    // Jika tidak ada, buat record baru
                    CsMainProjectClientSupport::create([
                        'cs_main_project_id' => $id,
                        'layanan' => $layanan,
                        'tanggal' => $tanggal,
                    ]);
                }
            }
        }

        // Proses kolom tanya_jawab untuk WebhostClientSupport
        $tanya_jawab = $clientSupportData->tanya_jawab ?? '';
        if (! empty($tanya_jawab)) {

            // Parse domain dari string tanya_jawab
            $domains = $instance->parseDomains($tanya_jawab);
            foreach ($domains as $domain) {

                // cari webhost_id berdasarkan nama domain
                $webhost = Webhost::where('nama_web', $domain)->first();
                if (! $webhost) {
                    continue;
                }

                // Cek apakah data sudah ada di tabel
                $existingRecord = WebhostClientSupport::where('webhost_id', $webhost->id_webhost)
                    ->where('layanan', $layanan)
                    ->where('tanggal', $tanggal)
                    ->first();

                if (! $existingRecord) {
                    // Jika tidak ada, buat record baru
                    WebhostClientSupport::create([
                        'webhost_id' => $webhost->id_webhost,
                        'layanan' => $layanan,
                        'tanggal' => $tanggal,
                    ]);
                }
            }
        }

        // Update kolom export menjadi 3 untuk menandai sudah diproses
        if ($clientSupportData) {
            DB::table('tb_clientsupport')
                ->where('id_cs_project', $clientSupportData->id_cs_project)
                ->update(['export' => 3]);
        }

        // respon
        return $result;
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
            if (! empty($normalized)) {
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
}
