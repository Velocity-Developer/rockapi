<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paket extends Model
{
    // Nama tabel di database
    protected $table = 'tb_paket';

    // Nama primary key yang tidak konvensional
    protected $primaryKey = 'id_paket';

    // tidak menggunakan timestamps
    public $timestamps = false;

    protected $fillable = [
        'paket',
        'bobot',
    ];

    // relasi many ke tabel webhost
    public function webhost()
    {
        return $this->hasMany(Webhost::class, 'id_paket');
    }
}
