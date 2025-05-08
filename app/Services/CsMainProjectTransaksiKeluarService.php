<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CsMainProjectTransaksiKeluarService
{

    public function getLaporanTransaksi($dari, $sampai)
    {
        $query1 = DB::table('tb_cs_main_project')
            ->selectRaw("
                id AS id_transaksi_cs_main,
                jenis,
                IFNULL((SELECT nama_web FROM tb_webhost WHERE id_webhost=tb_cs_main_project.id_webhost), '-') AS nama_web,
                IFNULL((SELECT id_webhost FROM tb_webhost WHERE id_webhost=tb_cs_main_project.id_webhost AND id_webhost NOT LIKE '%-%'), '-') AS id_webhost,
                tgl_masuk,
                tgl_deadline,
                deskripsi,
                trf,
                dibayar AS masuk,
                biaya,
                tgl_masuk AS tanggal,
                0 AS keluar,
                IFNULL((SELECT CONCAT(jenis,' ',deskripsi,' - ',
                    (SELECT nama_web FROM tb_webhost WHERE id_webhost=tb_cs_main_project.id_webhost))), '-') AS ket,
                '-' AS id_transaksi_keluar
            ")
            ->whereBetween('tgl_masuk', [$dari, $sampai]);

        $query2 = DB::table('tb_transaksi_keluar')
            ->selectRaw("
                NULL AS id_transaksi_cs_main,
                jenis,
                '-' AS nama_web,
                '-' AS id_webhost,
                tgl,
                NULL AS tgl_deadline,
                deskripsi,
                0 AS trf,
                0 AS masuk,
                0 AS biaya,
                jml AS keluar,
                tgl AS tanggal,
                CONCAT(jenis,' - ',deskripsi) AS ket,
                id_transaksi_keluar
            ")
            ->whereBetween('tgl', [$dari, $sampai]);

        $union = $query1->union($query2);

        return DB::table(DB::raw("({$union->toSql()}) as transaksi"))
            ->mergeBindings($union)
            ->orderBy('tgl_masuk') // atau 'tgl' kalau perlu fallback
            ->get();
    }
}
