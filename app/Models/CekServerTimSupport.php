<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CekServerTimSupport extends Model
{
    protected $fillable = [
        'server_id',
        'user_id',
        'hapus_backup_admin',
        'kapasitas_ssh',
        'tanggal_update_kapasitas_ssh',
        'cek_error_idrac',
        'tanggal_update_cek_error_idrac',
        'error_idrac'
    ];

    //cast
    protected $casts = [
        'tanggal_update_kapasitas_ssh' => 'datetime',
        'tanggal_update_cek_error_idrac' => 'datetime',
    ];

    // relasi one ke tabel server
    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    // relasi ke user pembuat data
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
