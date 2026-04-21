<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\CsMainProject;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class LeadAmController extends Controller
{
    public function index(Request $request)
    {
        // Step 1: Ambil filter tanggal dari request, default 30 hari terakhir.
        $dari = $request->input('dari')
            ? Carbon::parse($request->input('dari'))->toDateString()
            : now()->subDays(30)->toDateString();
        $sampai = $request->input('sampai')
            ? Carbon::parse($request->input('sampai'))->toDateString()
            : now()->toDateString();

        // Step 2: Ambil filter tambahan dan batasi jumlah data per halaman.
        $ma = trim((string) $request->input('ma', ''));
        $namaWeb = trim((string) $request->input('nama_web', ''));
        $perPage = (int) $request->input('per_page', $ma !== '' ? 10000 : 100);
        $perPage = max(1, min($perPage, 10000));

        // Step 3: Petakan field sort dari frontend ke kolom database yang valid.
        $sortMap = [
            'tgl_masuk' => 'tb_cs_main_project.tgl_masuk',
            'wa' => 'tb_webhost.wa',
            'paket' => 'tb_paket.paket',
            'nama_web' => 'tb_webhost.nama_web',
            'staff' => 'tb_cs_main_project.staff',
            'via' => 'tb_webhost.via',
            'konfirmasi_order' => 'tb_webhost.konfirmasi_order',
        ];
        $sortBy = $sortMap[$request->input('sort_by')] ?? 'tb_cs_main_project.tgl_masuk';
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Step 4: Buat query dasar yang sama untuk tabel utama dan ringkasan.
        // Gunakan left join agar project tetap tampil meski data webhost/paket tidak lengkap.
        $baseQuery = CsMainProject::query()
            ->leftJoin('tb_webhost', 'tb_webhost.id_webhost', '=', 'tb_cs_main_project.id_webhost')
            ->leftJoin('tb_paket', 'tb_webhost.id_paket', '=', 'tb_paket.id_paket')
            ->whereDate('tb_cs_main_project.tgl_masuk', '>=', $dari)
            ->whereDate('tb_cs_main_project.tgl_masuk', '<=', $sampai);

        // Step 5: Terapkan filter MA jika user mengisi nama MA.
        if ($ma !== '') {
            $baseQuery->where('tb_cs_main_project.staff', $ma);
        }

        // Step 6: Terapkan filter nama web/domain jika user mengisi pencarian.
        if ($namaWeb !== '') {
            $baseQuery->where('tb_webhost.nama_web', 'like', '%' . $namaWeb . '%');
        }

        // Step 7: Ambil data tabel lead AM sesuai filter, sorting, dan pagination.
        $leadQuery = (clone $baseQuery)
            ->select([
                'tb_cs_main_project.id',
                'tb_cs_main_project.id_webhost',
                'tb_cs_main_project.tgl_masuk',
                'tb_cs_main_project.jenis',
                'tb_cs_main_project.dibayar',
                'tb_cs_main_project.biaya',
                'tb_cs_main_project.staff',
                'tb_webhost.nama_web',
                'tb_webhost.wa',
                'tb_webhost.hp',
                'tb_webhost.via',
                'tb_webhost.waktu',
                'tb_webhost.konfirmasi_order',
                'tb_paket.paket',
            ])
            ->orderBy($sortBy, $sortOrder);

        $leads = $leadQuery->paginate($perPage);

        // Step 8: Buat ringkasan project Dini untuk chart di halaman Lead AM.
        $diniSummary = (clone $baseQuery)
            ->where('tb_cs_main_project.staff', 'Dini')
            ->select([
                'tb_cs_main_project.jenis',
                DB::raw('COUNT(*) as total'),
                DB::raw('COALESCE(SUM(tb_cs_main_project.biaya), 0) as biaya'),
            ])
            ->groupBy('tb_cs_main_project.jenis')
            ->orderBy('tb_cs_main_project.jenis')
            ->get();

        // Step 9: Kirim data utama, metadata filter, dan ringkasan ke frontend.
        return response()->json([
            'dari' => $dari,
            'sampai' => $sampai,
            'ma' => $ma,
            'nama_web' => $namaWeb,
            'data' => $leads,
            'dini_summary' => $diniSummary,
        ]);
    }

    public function updateStaff(Request $request, CsMainProject $project)
    {
        // Step 1: Validasi pilihan staff agar hanya nilai yang didukung yang bisa disimpan.
        $validated = $request->validate([
            'staff' => ['required', 'string', 'in:-,CS,Dini'],
        ]);

        // Step 2: Simpan staff baru ke record project yang dipilih.
        $project->staff = $validated['staff'];
        $project->save();

        // Step 3: Kembalikan response singkat untuk feedback frontend.
        return response()->json([
            'message' => 'Staff berhasil diperbarui',
            'data' => [
                'id' => $project->id,
                'staff' => $project->staff,
            ],
        ]);
    }
}
