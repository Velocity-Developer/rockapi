<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WmProject extends Model
{
    // Nama tabel di database
    protected $table = 'tb_wm_project';

    // Nama primary key yang tidak konvensional
    protected $primaryKey = 'id_wm_project';

    // tidak menggunakan timestamps
    public $timestamps = false;

    //cast
    protected $casts = [
        'user_id' => 'integer',
    ];

    //append
    protected $appends = [
        'quality_control',
        'progress',
        'date_mulai_formatted',
        'date_selesai_formatted',
    ];

    protected $fillable = [
        'id_karyawan',
        'user_id',
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

    //accessor quality_control
    public function getQualityControlAttribute()
    {
        if (!$this->qc) {
            return null;
        }

        return $this->qc ? unserialize($this->qc) : [];
    }

    public function getDateMulaiFormattedAttribute()
    {
        return $this->date_mulai ? Carbon::parse($this->date_mulai)->format('Y-m-d H:i:s') : null;
    }

    public function getDateSelesaiFormattedAttribute()
    {
        return $this->date_selesai ? Carbon::parse($this->date_selesai)->format('Y-m-d H:i:s') : null;
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

    //relasi one ke tabel user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
