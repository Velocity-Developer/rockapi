<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CsMainProject;
use Carbon\Carbon;
use App\Services\CsMainProjectTransaksiKeluarService;

class JenisBlmTerpilihController extends Controller
{
    //index
    public function index(Request $request, CsMainProjectTransaksiKeluarService $service)
    {
        $dari       = $request->input('tgl_masuk_start');
        $sampai      = $request->input('tgl_masuk_end');

        $get_data = $service->getLaporanTransaksi($dari, $sampai);

        //susun ulang data
        $data = [];
        foreach ($get_data as $item) {
            $tgl_masuk = Carbon::parse($item->tgl_masuk)->format('Y-m-d');

            if ($item->masuk == '0') {
                $id_transaksi = 'keluar-' . $item->id_transaksi_keluar;
                $kategori = 'keluar';
            } else {
                $id_transaksi = 'masuk-' . $item->id_transaksi_cs_main;
                $kategori = 'masuk';
            }

            $data[$tgl_masuk][$id_transaksi] = [
                'id_transaksi'  => $id_transaksi,
                'kategori'      => $kategori,
                'jenis'         => $item->jenis,
                'nama_web'      => $item->nama_web,
                'transfer'      => $item->trf,
                'deskripsi'     => $item->deskripsi,
                'biaya'         => $item->biaya,
                'dibayar'       => $item->masuk,
                'tgl'           => $item->tgl_masuk,
                'tgl_deadline'  => $item->tgl_deadline,
                'keluar'        => $item->keluar,
            ];
        }

        $list_keu = [];
        foreach ($data as $keu_top) {
            foreach ($keu_top as $key => $keu) {
                $list_keu[] = $keu;
            }
        }

        return response()->json([
            'raw'       => $get_data,
            'by_tgl'    => $data,
            'data'      => $list_keu
        ]);
    }
}
