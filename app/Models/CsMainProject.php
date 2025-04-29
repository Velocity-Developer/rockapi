<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsMainProject extends Model
{
    // Nama tabel di database
    protected $table = 'tb_cs_main_project';

    protected $fillable = [
        'id_webhost',
        'jenis',
        'deskripsi',
        'trf',
        'tgl_masuk',
        'tgl_deadline',
        'biaya',
        'dibayar',
        'status',
        'status_pm',
        'lunas',
        'staff',
        'dikerjakan_oleh',
        'tanda',
    ];

    //relasi ke tabel webhost
    public function webhost()
    {
        return $this->belongsTo(Webhost::class, 'id_webhost');
    }

    //relasi ke karyawan menggunakan pivot table cs_main_project_karyawan
    public function karyawans()
    {
        return $this->belongsToMany(
            Karyawan::class,
            'cs_main_project_karyawan',
            'cs_main_project_id', // foreign pivot key
            'karyawan_id',        // related pivot key
            'id',                 // local key di tb_cs_main_project
            'id_karyawan'         // local key di tb_karyawan
        )
            ->withPivot('porsi');
    }
}
