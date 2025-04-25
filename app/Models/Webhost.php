<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhost extends Model
{
    // Nama tabel di database
    protected $table = 'tb_webhost';

    // Nama primary key yang tidak konvensional
    protected $primaryKey = 'id_webhost';

    protected $fillable = [
        'nama_web',
        'id_paket',
        'tgl_mulai',
        'id_server',
        'id_server2',
        'space',
        'space_use',
        'hp',
        'telegram',
        'hpads',
        'wa',
        'email',
        'tgl_exp',
        'tgl_update',
        'server_luar',
        'saldo',
        'kategori',
        'waktu',
        'staff',
        'via',
        'konfirmasi_order',
        'kata_kunci',
        'jenis_kelamin',
        'usia',
    ];

    //relasi one ke tabel paket
    public function paket()
    {
        return $this->belongsTo(Paket::class, 'id_paket');
    }
}
