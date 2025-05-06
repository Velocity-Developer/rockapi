<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
 * Model CsMainProject
 * untuk tabel 'tb_cs_main_project'
 *
 * Tabel ini berisi data project yang masuk
 * menggunakan relasi ke Webhost untuk data Web / Customer nya
 *
*/

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

    protected $casts = [
        'biaya'     => 'integer',
        'dibayar'   => 'integer',
        'trf'       => 'integer',
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

    //relasi ke tabel bank_cs_main_project
    public function bank()
    {
        return $this->belongsToMany(Bank::class, 'bank_cs_main_project');
    }
}
