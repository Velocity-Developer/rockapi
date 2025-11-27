<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapForm extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'id';
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
        'created_at'
    ];
}
