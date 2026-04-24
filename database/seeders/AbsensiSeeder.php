<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\AbsensiRevisi;
use App\Models\AbsensiShift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AbsensiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'nama' => 'Pagi',
                'masuk' => '07:00:00',
                'pulang' => '15:30:00',
                'aktif' => true,
            ],
            [
                'nama' => 'Siang',
                'masuk' => '09:30:00',
                'pulang' => '18:00:00',
                'aktif' => true,
            ],
            [
                'nama' => 'Malam',
                'masuk' => '13:30:00',
                'pulang' => '22:00:00',
                'aktif' => true,
            ],
        ];

        $createdShifts = [];

        foreach ($shifts as $shiftData) {
            $shift = AbsensiShift::updateOrCreate(
                ['nama' => $shiftData['nama']],
                [
                    'masuk' => $shiftData['masuk'],
                    'pulang' => $shiftData['pulang'],
                    'aktif' => $shiftData['aktif'],
                ]
            );

            $createdShifts[$shift->nama] = $shift;
        }

        $users = User::query()->orderBy('id')->limit(3)->get();

        if ($users->isEmpty()) {
            $this->command?->warn('AbsensiSeeder: tidak ada user, hanya data shift yang dibuat.');

            return;
        }

        $tanggalList = [
            now()->subDays(2)->toDateString(),
            now()->subDay()->toDateString(),
            now()->toDateString(),
        ];

        foreach ($users as $index => $user) {
            $shift = $createdShifts[array_keys($createdShifts)[$index % count($createdShifts)]];
            $jamMasukShift = Carbon::parse($shift->masuk)->format('H:i:s');
            $jamPulangShift = Carbon::parse($shift->pulang)->format('H:i:s');

            foreach ($tanggalList as $dayIndex => $tanggal) {
                $masuk = Carbon::parse($tanggal . ' ' . $jamMasukShift)->addMinutes(($index * 5) + ($dayIndex * 3));
                $pulang = Carbon::parse($tanggal . ' ' . $jamPulangShift)->addMinutes(max(0, $dayIndex - 1) * 10);

                $status = match (true) {
                    $dayIndex === 1 && $index === 1 => Absensi::STATUS_TERLAMBAT,
                    $dayIndex === 2 && $index === 2 => Absensi::STATUS_SETENGAH_HARI,
                    default => Absensi::STATUS_HADIR,
                };

                $detikTelat = $status === Absensi::STATUS_TERLAMBAT
                    ? Carbon::parse($tanggal . ' ' . $jamMasukShift)->diffInSeconds($masuk)
                    : 0;

                $totalDetikKerja = $status === Absensi::STATUS_SETENGAH_HARI
                    ? 4 * 3600
                    : $masuk->diffInSeconds($pulang);

                $jamPulang = $status === Absensi::STATUS_SETENGAH_HARI
                    ? Carbon::parse($tanggal . ' 14:00:00')
                    : $pulang;

                Absensi::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'tanggal' => $tanggal,
                    ],
                    [
                        'absensi_shift_id' => $shift->id,
                        'status' => $status,
                        'catatan' => $status === Absensi::STATUS_SETENGAH_HARI ? 'Pulang lebih awal dengan izin.' : null,
                        'jam_masuk' => $masuk,
                        'jam_pulang' => $jamPulang,
                        'detik_telat' => $detikTelat,
                        'detik_pulang_cepat' => $status === Absensi::STATUS_SETENGAH_HARI ? 3 * 3600 : 0,
                        'detik_kurang' => 0,
                        'detik_lebih' => $status === Absensi::STATUS_HADIR && $dayIndex === 2 ? 1800 : 0,
                        'total_detik_kerja' => $totalDetikKerja,
                        'nama_shift' => $shift->nama,
                        'jadwal_masuk' => $jamMasukShift,
                        'jadwal_pulang' => $jamPulangShift,
                    ]
                );
            }
        }

        $firstUser = $users->first();
        $secondUser = $users->skip(1)->first() ?? $firstUser;

        $revisiRows = [
            [
                'user_id' => $firstUser->id,
                'tanggal' => now()->subDay()->toDateString(),
                'detik' => 1800,
                'jenis' => AbsensiRevisi::JENIS_TAMBAH,
                'sumber' => 'Lembur',
                'approve_user_id' => $secondUser?->id,
                'catatan' => 'Tambahan 30 menit karena penyelesaian pekerjaan.',
            ],
            [
                'user_id' => $secondUser->id,
                'tanggal' => now()->toDateString(),
                'detik' => 900,
                'jenis' => AbsensiRevisi::JENIS_KURANG,
                'sumber' => 'Penyesuaian Admin',
                'approve_user_id' => $firstUser->id,
                'catatan' => 'Pengurangan 15 menit untuk koreksi perhitungan.',
            ],
        ];

        foreach ($revisiRows as $revisiData) {
            AbsensiRevisi::updateOrCreate(
                [
                    'user_id' => $revisiData['user_id'],
                    'tanggal' => $revisiData['tanggal'],
                    'jenis' => $revisiData['jenis'],
                    'sumber' => $revisiData['sumber'],
                ],
                [
                    'detik' => $revisiData['detik'],
                    'approve_user_id' => $revisiData['approve_user_id'],
                    'catatan' => $revisiData['catatan'],
                ]
            );
        }

        $this->command?->info('AbsensiSeeder selesai: shift, absensi, dan revisi berhasil dibuat.');
    }
}
