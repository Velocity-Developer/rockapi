<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PmProject extends Model
{
    // Nama tabel di database
    protected $table = 'tb_pm_project';

    // Nama primary key yang tidak konvensional
    protected $primaryKey = 'id_pm_project';

    // tidak menggunakan timestamps
    public $timestamps = false;

    protected $fillable = [
        'id',
        'konfirm_revisi_1',
        'fr1',
        'tutorial_password',
        'selesai',
    ];

    // relasi one ke tabel cs_main_project
    public function csMainProject()
    {
        return $this->belongsTo(CsMainProject::class, 'id');
    }
}
