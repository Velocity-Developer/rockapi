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

    //append
    protected $appends = [
        'progress',
    ];

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

    //accessor progress
    public function getProgressAttribute()
    {
        if ($this->status_multi == 'selesai') {
            return (int) 100;
        }

        $qc = $this->qc ? unserialize($this->qc) : [];
        if ($qc) {
            //hitung total Quality
            $total_q    = Quality::count();
            $total_qc   = count($qc);
            $percentage = round((($total_qc / $total_q) * 100), 2);
            return (int) $percentage;
        } else {
            return (int) 0;
        }
    }

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
