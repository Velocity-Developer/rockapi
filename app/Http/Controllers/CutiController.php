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

    private function totalMenit(array $times): int
    {
        $totalMenit = 0;

        foreach ($times as $time) {
            if (is_numeric($time)) {
                $totalMenit += (int) $time * 60;
                continue;
            }

            if (!is_string($time) || !str_contains($time, ':')) {
                continue;
            }

            [$jam, $menit] = array_map('intval', explode(':', $time));
            $totalMenit += ($jam * 60) + $menit;
        }

        return $totalMenit;
    }

    private function formatHariDecimal(array $times): string|int
    {
        $totalJam = $this->totalMenit($times) / 60;

        // jika total jam 0, maka return 0
        if ($totalJam === 0) {
            return 0;
        }

        $hasil = $totalJam / 7;

        // jika bilangan bulat, tampilkan tanpa .00
        if ($hasil == floor($hasil)) {
            return (int) $hasil;
        }

        return number_format($hasil, 2, '.', '');
    }

    private function formatHariJamMenit(array $times): string|bool
    {
        $totalMenit = $this->totalMenit($times);

        if ($totalMenit === 0) {
            return false;
        }

        $totalJam   = intdiv($totalMenit, 60);
        $sisaMenit  = $totalMenit % 60;

        $hari = intdiv($totalJam, 7);
        $jam  = $totalJam % 7;

        $result = [];

        // tampilkan hari hanya jika > 0
        if ($hari > 0) {
            $result[] = sprintf('%02d Hari', $hari);
        }

        $result[] = sprintf('%02d Jam', $jam);
        $result[] = sprintf('%02d Menit', $sisaMenit);

        return implode(' ', $result);
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
            $total = $tambahan = [];
            foreach ($rows->values() as $item) {
                if ($item['jenis'] == 'Full') {
                    $total[$item['detail']][] = $item['tanggal'];
                }
                if ($item['jenis'] == 'Jam') {
                    $tambahan[$item['tipe']][] = $item['time'];
                }
            }

            $tambahan['Sakit'][] = isset($total['Sakit']) ? (count($total['Sakit']) * 7) : 0;
            $cutiFull = isset($total['Cuti']) ? (count($total['Cuti']) * 7) : 0;
            $blmdiganti = $this->formatHariJamMenit($tambahan['Belum diganti'] ?? []);

            return [
                'nama'      => $nama,
                'total'     => $rows->count(),
                'items'     => $rows->values(),
                'totals'    => $total,
                'tambahan'  => $tambahan,
                'detail'    => [
                    'Sakit' => $this->formatHariDecimal($tambahan['Sakit'] ?? []),
                    'Cuti' => $this->formatHariDecimal([$cutiFull]),
                    'Blm diganti' => $this->formatHariJamMenit($tambahan['Belum diganti'] ?? []),
                ],
                'sakit' => $this->formatHariDecimal($tambahan['Sakit'] ?? []),
                'cuti' => $this->formatHariDecimal([$cutiFull]),
                'blmdiganti' => $blmdiganti,
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
