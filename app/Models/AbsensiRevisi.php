<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiRevisi extends Model
{
    protected $table = 'absensi_revisi';

    protected $fillable = [
        'user_id',
        'tanggal',
        'detik',
        'jenis',
        'sumber',
        'approve_user_id',
        'catatan',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'tanggal' => 'date:Y-m-d',
        'detik' => 'integer',
        'approve_user_id' => 'integer',
    ];

    public const JENIS_TAMBAH = 'Tambah';

    public const JENIS_KURANG = 'Kurang';

    public const SUMBER = ['Tambah Jam Pulang', 'Masuk Lebih Awal', 'Kerja Saat Istirahat', 'Lembur', 'Penyesuaian Admin'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approve_user_id');
    }
}
