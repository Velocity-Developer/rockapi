<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuti extends Model
{
    // Nama tabel di database
    protected $table = 'tb_cuti';

    // tidak menggunakan timestamps
    public $timestamps = false;

    protected $fillable = [
        'nama',
        'tanggal',
        'jenis',
        'time',
        'tipe',
        'detail'
    ];
}
