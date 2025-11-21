<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhostClientSupport extends Model
{
    protected $fillable = [
        'webhost_id',
        'layanan',
        'tanggal',
        'user_id',
    ];

    //hide
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    //cast
    protected $casts = [
        'webhost_id' => 'integer',
        'user_id' => 'integer',
    ];

    //relasi ke Webhost
    public function webhost()
    {
        return $this->belongsTo(Webhost::class, 'webhost_id');
    }

    //relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
