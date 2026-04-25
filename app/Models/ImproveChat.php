<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ImproveChat extends Model
{
    public const KATEGORI = [
        'customer_service' => 'CS',
        'revisi' => 'Revisi',
        'support' => 'Support',
        'am' => 'AM',
        'advertising' => 'Ads',
        'manager_project' => 'PM',
    ];

    protected $fillable = [
        'nohp',
        'kategori',
        'masukkan',
        'user_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    // relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (Auth::check() && empty($model->user_id)) {
                $model->user_id = Auth::id();
            }
        });
    }
}
