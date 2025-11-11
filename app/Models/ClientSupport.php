<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSupport extends Model
{
    // Nama tabel di database
    protected $table = 'tb_clientsupport';

    // tidak menggunakan timestamps
    public $timestamps = false;

    protected $fillable = [
        'id_cs_project',
        'tgl',
        'revisi_1',
        'perbaikan_revisi_1',
        'revisi_2',
        'perbaikan_revisi_2',
        'tanya_jawab',
        'update_web',
        'export',
    ];
}
