<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ini adalah model Konversi.
 * menggabungkan data konversi dari tabel 'tb_konversi','tb_konversi_display','tb_konversi_wa5','tb_konversi_organik'.
 * dalam satu tabel.
 */
class Konversi extends Model
{
    protected $table = 'konversi';

    protected $fillable = [
        'date',
        'value',
        'kategori',
    ];
}
