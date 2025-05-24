<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WmProject extends Model
{
    // Nama tabel di database
    protected $table = 'tb_wm_project';

    // Nama primary key yang tidak konvensional
    protected $primaryKey = 'id_wm_project';

    // tidak menggunakan timestamps
    public $timestamps = false;

    protected $fillable = [
        'id_karyawan',
        'id',
        'start',
        'stop',
        'durasi',
        'webmaster',
        'date_mulai',
        'date_selesai',
        'qc',
        'catatan',
        'status_multi',
    ];

    //relasi one ke tabel cs_main_project
    public function cs_main_project()
    {
        return $this->belongsTo(CsMainProject::class, 'id');
    }

    //relasi one ke tabel karyawan
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }
}
