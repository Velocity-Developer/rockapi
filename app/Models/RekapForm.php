<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class RekapForm extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'id',
        'source',
        'source_id',
        'nama',
        'no_whatsapp',
        'jenis_website',
        'ai_result',
        'via',
        'utm_content',
        'utm_medium',
        'greeting',
        'status',
        'label',
        'gclid',
        'cek_konversi_ads',
        'kategori_konversi_nominal',
        'cek_konversi_nominal',
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

    // relasi dengan log konversi
    public function log_konversi()
    {
        return $this->hasMany(RekapFormsLogKonversi::class);
    }
}
