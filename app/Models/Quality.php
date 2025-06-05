<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quality extends Model
{
    // Nama tabel di database
    protected $table = 'tb_quality';

    // tidak menggunakan timestamps
    public $timestamps = false;
}
