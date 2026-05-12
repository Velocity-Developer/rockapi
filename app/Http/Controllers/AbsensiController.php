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
        $query = Absensi::with(['user', 'shift', 'media']);

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

    public function totalStatusByUser(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
        ]);

        $statuses = [
            Absensi::STATUS_HADIR,
            Absensi::STATUS_IZIN,
            Absensi::STATUS_SAKIT,
            Absensi::STATUS_CUTI,
            Absensi::STATUS_ALPHA,
            Absensi::STATUS_LIBUR,
            Absensi::STATUS_SETENGAH_HARI,
        ];

        $rows = Absensi::query()
            ->selectRaw('status, COUNT(*) as total')
            ->where('user_id', $validated['user_id'])
            ->when($validated['tanggal_mulai'] ?? null, function ($query, $tanggalMulai) {
                $query->whereDate('tanggal', '>=', $tanggalMulai);
            })
            ->when($validated['tanggal_selesai'] ?? null, function ($query, $tanggalSelesai) {
                $query->whereDate('tanggal', '<=', $tanggalSelesai);
            })
            ->groupBy('status')
            ->pluck('total', 'status');

        $byStatus = collect($statuses)
            ->mapWithKeys(function ($status) use ($rows) {
                return [$status => (int) ($rows[$status] ?? 0)];
            })
            ->all();

        if (isset($rows[Absensi::STATUS_TERLAMBAT])) {
            $byStatus[Absensi::STATUS_HADIR] += (int) $rows[Absensi::STATUS_TERLAMBAT];
        }

        return response()->json([
            'user_id' => (int) $validated['user_id'],
            'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
            'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
            'total' => array_sum($byStatus),
            'by_status' => $byStatus,
        ]);
    }

    public function store(Request $request)
    {
        $this->normalizeRequestStatus($request);
        $validated = $request->validate($this->rules());
        $validated = $this->fillAutomaticWorkDuration($validated);

        $absensi = Absensi::create($validated);
        $this->syncLampiranIzin($request, $absensi);
        $absensi->load(['user', 'shift', 'media']);

        return response()->json($absensi, 201);
    }

    public function show(string $id)
    {
        $absensi = Absensi::with(['user', 'shift', 'media'])->findOrFail($id);

        return response()->json($absensi);
    }

    public function update(Request $request, string $id)
    {
        $this->normalizeRequestStatus($request);
        $validated = $request->validate($this->rules());
        $validated = $this->fillAutomaticWorkDuration($validated);

        $absensi = Absensi::findOrFail($id);
        $absensi->update($validated);
        $this->syncLampiranIzin($request, $absensi);

        return response()->json($absensi->load(['user', 'shift', 'media']));
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
            'lampiran_izin' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    private function syncLampiranIzin(Request $request, Absensi $absensi): void
    {
        if ($absensi->status !== Absensi::STATUS_SAKIT) {
            $absensi->clearMediaCollection('lampiran_izin');

            return;
        }

        if ($request->hasFile('lampiran_izin') && $request->file('lampiran_izin')->isValid()) {
            $absensi->clearMediaCollection('lampiran_izin');
            $absensi->addMedia($request->file('lampiran_izin'))
                ->toMediaCollection('lampiran_izin');
        }
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
