<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowupAdvertiser extends Model
{
    // Nama tabel di database
    protected $table = 'tb_followup_advertiser';

    // tidak menggunakan timestamps
    public $timestamps = false;

    protected $fillable = [
        'id_webhost_ads',
        'status_ads',
        'update_ads',
    ];

    // relasi many ke tabel webhost
    public function journals()
    {
        return $this->belongsTo(Webhost::class, 'webhost_id');
    }
}
