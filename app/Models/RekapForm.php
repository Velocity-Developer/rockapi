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
        'created_at',
        'updated_at'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
