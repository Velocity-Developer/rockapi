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
        'bulan',
        'biaya',
    ];
}
