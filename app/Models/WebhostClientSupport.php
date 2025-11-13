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

    //hide
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    //cast
    protected $casts = [
        'webhost_id' => 'integer',
    ];

    //relasi ke Webhost
    public function webhost()
    {
        return $this->belongsTo(Webhost::class, 'webhost_id');
    }
}
