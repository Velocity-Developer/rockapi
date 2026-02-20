<?php

namespace App\Http\Controllers;

use App\Models\Cuti;
use Illuminate\Http\Request;

class CutiController extends Controller
{

    public $karyawans = [
        'Aditya',
        'Aditya K',
        'Agus',
        'Bima',
        'Dita',
        'Eko',
        'Ihsan',
        'Irawan',
        'Galib',
        'Muh',
        'Siti',
        'Yuda',
        'Kendra',
        'Yoga',
        'Viki',
        'ayu',
        'Sudqi',
        'Sofian',
        'Fajar',
        'Lingga',
        'Reza',
        'Anggun',
        'Dini',
        'Rosa',
        'Putri',
        'Fajar Agung',
        'Erna',
        'Niken',
        'Yaya',
        'Afif'
    ];

    private function tambahJamMenit(string $jam1, string $jam2): string
    {
        [$h1, $m1] = array_map('intval', explode(':', $jam1));
        [$h2, $m2] = array_map('intval', explode(':', $jam2));

        $totalMenit = ($h1 * 60 + $m1) + ($h2 * 60 + $m2);

        $jam   = floor($totalMenit / 60) % 24;
        $menit = $totalMenit % 60;

        return sprintf('%02d:%02d', $jam, $menit);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Cuti::query();

        if ($request->input('tahun')) {
            $query->whereYear('tanggal', $request->input('tahun'));
        }

        if ($request->input('nama')) {
            $query->where('nama', 'like', '%' . $request->input('nama') . '%');
        }

        if ($request->input('jenis')) {
            $query->where('jenis', $request->input('jenis'));
        }

        if ($request->input('tipe')) {
            $query->where('tipe', $request->input('tipe'));
        }

        if ($request->input('tanggal')) {
            $query->whereDate('tanggal', $request->input('tanggal'));
        }

        if ($request->input('tanggal_from')) {
            $query->whereDate('tanggal', '>=', $request->input('tanggal_from'));
        }

        if ($request->input('tanggal_to')) {
            $query->whereDate('tanggal', '<=', $request->input('tanggal_to'));
        }

        $query->orderBy('tanggal', 'asc');

        $items = $query->get();

        $grouped = $items->groupBy('nama')->map(function ($rows, $nama) {

            $sakit = 0;
            $cuti = 0;
            $blm_diganti = '00:00';
            $total = $tambahan = [];
            foreach ($rows->values() as $item) {

                if ($item->detail == 'Sakit' && $item->time == "00:00") {
                    $sakit += 1;
                }
                if ($item->detail == 'Cuti' && $item->jenis == 'Full') {
                    $cuti += 1;
                }
                if ($item->jenis == 'Jam' && $item->time !== "00:00" && $item->tipe == "Belum diganti") {
                    $blm_diganti = $this->tambahJamMenit($blm_diganti, $item->time);
                }

                if ($item['jenis'] == 'Full') {
                    $total[$item['detail']][] = $item['tanggal'];
                }
                if ($item['jenis'] == 'Jam') {
                    $tambahan[$item['tipe']][] = $item['time'];
                }
            }

            $tambahan['Sakit'][] = isset($total['Sakit']) ? (count($total['Sakit']) * 7) : 0;
            $tambahan['Belum diganti'][] = isset($total['Cuti']) ? (count($total['Cuti']) * 7) : 0;

            return [
                'nama'      => $nama,
                'total'     => $rows->count(),
                'items'     => $rows->values(),
                'totals'    => $total,
                'tambahan'  => $tambahan,
                'detail'    => [
                    'Sakit' => $sakit,
                    'Cuti' => $cuti,
                    'Blm diganti' => $blm_diganti,
                ],
            ];
        })->values();

        return response()->json([
            'karyawans' => $this->karyawans,
            'data' => $grouped
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'jenis' => 'nullable|string|max:255',
            'time' => 'nullable|string|max:50',
            'tipe' => 'nullable|string|max:50',
            'detail' => 'nullable|string',
        ]);

        $cuti = new Cuti();
        $cuti->nama = $request->nama;
        $cuti->tanggal = $request->tanggal;
        $cuti->jenis = $request->jenis;
        $cuti->time = $request->time;
        $cuti->tipe = $request->tipe;
        $cuti->detail = $request->detail;
        $cuti->save();

        return response()->json($cuti, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cuti = Cuti::findOrFail($id);

        return response()->json($cuti);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'jenis' => 'nullable|string|max:255',
            'time' => 'nullable|string|max:50',
            'tipe' => 'nullable|string|max:50',
            'detail' => 'nullable|string',
        ]);

        $cuti = Cuti::findOrFail($id);
        $cuti->nama = $request->nama;
        $cuti->tanggal = $request->tanggal;
        $cuti->jenis = $request->jenis;
        $cuti->time = $request->time;
        $cuti->tipe = $request->tipe;
        $cuti->detail = $request->detail;
        $cuti->save();

        return response()->json($cuti);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cuti = Cuti::findOrFail($id);
        $cuti->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cuti deleted successfully',
        ]);
    }
}
