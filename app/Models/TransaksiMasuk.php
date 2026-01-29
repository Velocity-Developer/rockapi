<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiMasuk extends Model
{
    // Nama tabel di database
    protected $table = 'tb_transaksi_masuk';

    // Nama primary key yang tidak konvensional
    protected $primaryKey = 'id_transaksi_masuk';

    // tidak menggunakan timestamps
    public $timestamps = false;

    protected $fillable = [
        'id',
        'tgl',
        'total_biaya',
        'bayar',
        'pelunasan',
    ];

    // relasi one ke tabel cs_main_project
    public function csMainProject()
    {
        return $this->belongsTo(CsMainProject::class, 'id');
    }
}
