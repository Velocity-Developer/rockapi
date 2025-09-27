<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhostClientSupport extends Model
{
    protected $fillable = [
        'webhost_id',
        'layanan',
        'tanggal',
    ];

    //relasi ke Webhost
    public function webhost()
    {
        return $this->belongsTo(Webhost::class, 'webhost_id');
    }
}
