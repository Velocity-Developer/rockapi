<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiKeluar extends Model
{
    // Nama tabel di database
    protected $table = 'tb_transaksi_keluar';

    // Nama primary key yang tidak konvensional
    protected $primaryKey = 'id_transaksi_keluar';

    protected $fillable = [
        'id_transaksi_keluar',
        'tgl',
        'jml',
        'jenis',
        'deskripsi',
    ];

    protected $casts = [
        'jml' => 'integer',
    ];


    //relasi ke tabel bank
    public function bank()
    {
        return $this->belongsToMany(Bank::class, 'bank_transaksi_keluar', 'transaksi_keluar_id', 'bank_id');
    }
}
