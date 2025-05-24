<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Models\CsMainProject;
use App\Models\TransaksiKeluar;
use Carbon\Carbon;

class JenisBlmTerpilihController extends Controller
{
    //index
    public function index(Request $request)
    {
        $dari   = $request->input('tgl_masuk_start');
        $sampai = $request->input('tgl_masuk_end');

        //jika $dari kosong, gunakan tanggal di awal bulan sekarang
        if (empty($dari)) {
            //30 hari terakhir
            $dari = Carbon::now()->subDays(30)->format('Y-m-d');
        }
        //jika $sampai kosong, gunakan tanggal di tanggal sekarang
        if (empty($sampai)) {
            $sampai = Carbon::now()->format('Y-m-d');
        }

        //ambil data CsMainProject
        $dataCsMainProject = CsMainProject::with('webhost', 'bank')
            ->whereBetween('tgl_masuk', [$dari, $sampai])
            ->whereDoesntHave('bank') // tidak memiliki relasi ke bank
            ->get()
            ->makeHidden('dikerjakan_oleh')
            ->map(function ($item) {
                // Tambahkan properti virtual 'tanggal' agar bisa di-sort bersamaan
                $item->tanggal = $item->tgl_masuk;
                $item->tipe = 'masuk';
                $item->nama_web = $item->webhost->nama_web;
                return $item;
            });

        //ambil data TransaksiKeluar
        $dataTransaksiKeluar = TransaksiKeluar::with('bank')
            ->whereBetween('tgl', [$dari, $sampai])
            ->whereDoesntHave('bank') // tidak memiliki relasi ke bank
            ->get()
            ->map(function ($item) {
                // Tambahkan properti virtual 'tanggal'
                $item->tanggal = $item->tgl;
                $item->tipe = 'keluar';
                return $item;
            });

        $gabungan = $dataCsMainProject->merge($dataTransaksiKeluar)->sortByDesc('tanggal')->values();

        $perPage = $request->input('per_page') ?? 50; // jumlah item per halaman
        $page = $request->input('page') ?? 1; // dapatkan nomor halaman dari URL

        $paginated = new LengthAwarePaginator(
            $gabungan->forPage($page, $perPage),
            $gabungan->count(), // total item
            $perPage,
            $page,
            [
                'path' => 'jenis_blm_terpilih',
                // 'query' => request()->query() // agar pagination mempertahankan query string
            ]
        );

        return response()->json($paginated);
    }
}
