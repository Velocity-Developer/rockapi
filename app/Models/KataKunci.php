<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KataKunci extends Model
{
    // Nama tabel di database
    protected $table = 'tb_kk';

    // tidak menggunakan timestamps
    public $timestamps = false;

    protected $fillable = [
        'kata_kunci',
        'grup_iklan',
        'id_grup_iklan',
        'nomor_kata_kunci',
        'greeting',
    ];

    //relasi dengan rekap chat
    public function rekap_chat()
    {
        return $this->hasMany(RekapChat::class, 'kata_kunci', 'greeting');
    }
}
