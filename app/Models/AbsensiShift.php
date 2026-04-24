<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbsensiShift extends Model
{
    protected $table = 'absensi_shift';

    protected $fillable = [
        'nama',
        'masuk',
        'pulang',
        'aktif',
    ];

    protected $casts = [
        'masuk' => 'datetime:H:i:s',
        'pulang' => 'datetime:H:i:s',
        'aktif' => 'boolean',
    ];

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'absensi_shift_id');
    }
}
