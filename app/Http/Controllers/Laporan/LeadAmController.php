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
        $dari = $request->input('dari')
            ? Carbon::parse($request->input('dari'))->toDateString()
            : now()->subDays(30)->toDateString();
        $sampai = $request->input('sampai')
            ? Carbon::parse($request->input('sampai'))->toDateString()
            : now()->toDateString();

        $ma = trim((string) $request->input('ma', ''));
        $namaWeb = trim((string) $request->input('nama_web', ''));
        $perPage = (int) $request->input('per_page', $ma !== '' ? 10000 : 100);
        $perPage = max(1, min($perPage, 10000));

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

        $baseQuery = CsMainProject::query()
            ->join('tb_webhost', 'tb_webhost.id_webhost', '=', 'tb_cs_main_project.id_webhost')
            ->join('tb_paket', 'tb_webhost.id_paket', '=', 'tb_paket.id_paket')
            ->whereDate('tb_cs_main_project.tgl_masuk', '>=', $dari)
            ->whereDate('tb_cs_main_project.tgl_masuk', '<=', $sampai);

        if ($ma !== '') {
            $baseQuery->where('tb_cs_main_project.staff', $ma);
        }

        if ($namaWeb !== '') {
            $baseQuery->where('tb_webhost.nama_web', 'like', '%'.$namaWeb.'%');
        }

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
        $validated = $request->validate([
            'staff' => ['required', 'string', 'in:-,CS,Dini'],
        ]);

        $project->staff = $validated['staff'];
        $project->save();

        return response()->json([
            'message' => 'Staff berhasil diperbarui',
            'data' => [
                'id' => $project->id,
                'staff' => $project->staff,
            ],
        ]);
    }
}
