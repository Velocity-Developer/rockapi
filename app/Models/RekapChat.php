<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapChat extends Model
{
    // Nama tabel di database
    protected $table = 'tb_rekap_chat';

    // tidak menggunakan timestamps
    public $timestamps = false;

    protected $fillable = [
        'whatsapp',
        'chat_pertama',
        'via',
        'perangkat',
        'alasan',
        'detail',
        'kata_kunci',
        'tanggal_followup',
        'status_followup',
    ];

    //relasi dengan kata kunci
    public function kk()
    {
        return $this->hasOne(KataKunci::class, 'greeting', 'kata_kunci');
    }
}
