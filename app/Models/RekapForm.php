<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    public function getCreatedAtAttribute($value)
    {
        return str_replace('Z', '+07:00', $value);
    }

    public function getCreatedAtWibAttribute()
    {
        return Carbon::parse(
            $this->getRawOriginal('created_at'),
            'Asia/Jakarta'
        )->format('Y-m-d H:i:sP');
    }
}
