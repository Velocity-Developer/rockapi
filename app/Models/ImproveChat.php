<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ImproveChat extends Model
{
    public const KATEGORI = [
        'CS',
        'Revisi',
        'Support',
        'AM',
        'Ads',
        'PM'
    ];

    protected $fillable = [
        'nohp',
        'kategori',
        'masukkan',
        'user_id',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (Auth::check() && empty($model->user_id)) {
                $model->user_id = Auth::id();
            }
        });
    }
}
