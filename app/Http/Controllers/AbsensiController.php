<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $query = Absensi::with(['user', 'shift']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->input('tanggal'));
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->input('tanggal_mulai'));
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->input('tanggal_selesai'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('absensi_shift_id')) {
            $query->where('absensi_shift_id', $request->input('absensi_shift_id'));
        }

        $orderBy = $request->input('order_by', 'tanggal');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->input('per_page', 50);
        $pagination = filter_var($request->query('pagination', true), FILTER_VALIDATE_BOOLEAN);

        $allowedOrderBy = [
            'id',
            'user_id',
            'tanggal',
            'status',
            'jam_masuk',
            'jam_pulang',
            'created_at',
            'updated_at',
        ];

        if (! in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'tanggal';
        }

        $query->orderBy($orderBy, $order)->orderBy('id', 'desc');

        if ($pagination) {
            $data = $query->paginate($perPage);
        } else {
            $data = [
                'data' => $query->limit($perPage)->get(),
            ];
        }

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $absensi = Absensi::create($validated)->load(['user', 'shift']);

        return response()->json($absensi, 201);
    }

    public function show(string $id)
    {
        $absensi = Absensi::with(['user', 'shift'])->findOrFail($id);

        return response()->json($absensi);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate($this->rules());

        $absensi = Absensi::findOrFail($id);
        $absensi->update($validated);

        return response()->json($absensi->load(['user', 'shift']));
    }

    public function destroy(string $id)
    {
        $absensi = Absensi::findOrFail($id);
        $absensi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Absensi deleted successfully',
        ]);
    }

    private function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'tanggal' => ['required', 'date'],
            'absensi_shift_id' => ['nullable', 'integer', 'exists:absensi_shift,id'],
            'status' => [
                'required',
                'string',
                Rule::in([
                    Absensi::STATUS_HADIR,
                    Absensi::STATUS_TERLAMBAT,
                    Absensi::STATUS_IZIN,
                    Absensi::STATUS_SAKIT,
                    Absensi::STATUS_CUTI,
                    Absensi::STATUS_ALPHA,
                    Absensi::STATUS_LIBUR,
                    Absensi::STATUS_SETENGAH_HARI,
                ]),
            ],
            'catatan' => ['nullable', 'string'],
            'jam_masuk' => ['nullable', 'date'],
            'jam_pulang' => ['nullable', 'date'],
            'detik_telat' => ['nullable', 'integer'],
            'detik_pulang_cepat' => ['nullable', 'integer'],
            'detik_kurang' => ['nullable', 'integer'],
            'detik_lebih' => ['nullable', 'integer'],
            'total_detik_kerja' => ['nullable', 'integer'],
            'nama_shift' => ['nullable', 'string', 'max:255'],
            'jadwal_masuk' => ['nullable', 'date_format:H:i:s'],
            'jadwal_pulang' => ['nullable', 'date_format:H:i:s'],
        ];
    }
}
