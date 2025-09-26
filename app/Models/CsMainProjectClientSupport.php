<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsMainProjectClientSupport extends Model
{
    protected $fillable = [
        'cs_main_project_id',
        'layanan',
        'tanggal',
    ];

    //relasi ke CsMainProject
    public function cs_main_project()
    {
        return $this->belongsTo(CsMainProject::class, 'cs_main_project_id');
    }
}
