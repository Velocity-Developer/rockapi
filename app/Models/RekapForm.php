<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapForm extends Model
{
    public $timestamps = true;
    protected $fillable = [
        'id',
        'nama',
        'no_whatsapp',
        'jenis_website',
        'ai_result',
        'via',
        'utm_content',
        'utm_medium',
        'greeting',
        'status',
        'gclid',
        'cek_konversi_ads',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['created_at_wib'];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function getCreatedAtWibAttribute()
    {
        return $this->created_at
            ->copy()
            ->setTimezone('Asia/Jakarta')
            ->format('Y-m-d H:i:sP');
    }
}
