<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\AbsensiShift;
use Carbon\Carbon;
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
            if ($request->input('status') === Absensi::STATUS_HADIR) {
                $query->whereIn('status', [
                    Absensi::STATUS_HADIR,
                    Absensi::STATUS_TERLAMBAT,
                ]);
            } else {
                $query->where('status', $request->input('status'));
            }
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
        $this->normalizeRequestStatus($request);
        $validated = $request->validate($this->rules());
        $validated = $this->fillAutomaticWorkDuration($validated);

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
        $this->normalizeRequestStatus($request);
        $validated = $request->validate($this->rules());
        $validated = $this->fillAutomaticWorkDuration($validated);

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

    private function normalizeRequestStatus(Request $request): void
    {
        if ($request->input('status') === Absensi::STATUS_TERLAMBAT) {
            $request->merge(['status' => Absensi::STATUS_HADIR]);
        }
    }

    private function fillAutomaticWorkDuration(array $data): array
    {
        $jamMasuk = $this->parseDateTime($data['jam_masuk'] ?? null);

        if (! $jamMasuk) {
            $data['total_detik_kerja'] = 0;

            return $data;
        }

        $jamPulang = $this->parseDateTime($data['jam_pulang'] ?? null);
        $targetPulang = $jamPulang ?? $this->scheduledCheckoutAt($data, $jamMasuk);

        if (! $targetPulang) {
            $data['total_detik_kerja'] = 0;

            return $data;
        }

        if (! $targetPulang->greaterThan($jamMasuk)) {
            $targetPulang = $targetPulang->copy()->addDay();
        }

        $data['total_detik_kerja'] = (int) max($jamMasuk->diffInSeconds($targetPulang, false), 0);

        return $data;
    }

    private function scheduledCheckoutAt(array $data, Carbon $jamMasuk): ?Carbon
    {
        $jadwalPulang = $data['jadwal_pulang'] ?? null;

        if (! $jadwalPulang && ! empty($data['absensi_shift_id'])) {
            $jadwalPulang = AbsensiShift::query()
                ->whereKey($data['absensi_shift_id'])
                ->value('pulang');
        }

        if (! $jadwalPulang) {
            return null;
        }

        $tanggal = $data['tanggal'] ?? $jamMasuk->toDateString();

        return Carbon::parse($tanggal.' '.$jadwalPulang);
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value);
    }
}
