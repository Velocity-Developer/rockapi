<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    protected $table = 'absensi';

    protected $fillable = [
        'user_id',
        'tanggal',
        'absensi_shift_id',
        'status',
        'catatan',
        'jam_masuk',
        'jam_pulang',
        'detik_telat',
        'detik_pulang_cepat',
        'detik_kurang',
        'detik_lebih',
        'total_detik_kerja',
        'nama_shift',
        'jadwal_masuk',
        'jadwal_pulang',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'tanggal' => 'date:Y-m-d',
        'absensi_shift_id' => 'integer',
        'jam_masuk' => 'datetime:Y-m-d H:i:s',
        'jam_pulang' => 'datetime:Y-m-d H:i:s',
        'detik_telat' => 'integer',
        'detik_pulang_cepat' => 'integer',
        'detik_kurang' => 'integer',
        'detik_lebih' => 'integer',
        'total_detik_kerja' => 'integer',
        'jadwal_masuk' => 'datetime:H:i:s',
        'jadwal_pulang' => 'datetime:H:i:s',
    ];

    public const STATUS_HADIR = 'Hadir';

    public const STATUS_TERLAMBAT = 'Terlambat';

    public const STATUS_IZIN = 'Izin';

    public const STATUS_SAKIT = 'Sakit';

    public const STATUS_CUTI = 'Cuti';

    public const STATUS_ALPHA = 'Alpha';

    public const STATUS_LIBUR = 'Libur';

    public const STATUS_SETENGAH_HARI = 'Setengah Hari';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(AbsensiShift::class, 'absensi_shift_id');
    }
}
