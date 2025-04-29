<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    // Nama tabel di database
    protected $table = 'tb_karyawan';

    // Nama primary key yang tidak konvensional
    protected $primaryKey = 'id_karyawan';
    protected $keyType = 'int';

    //hidden
    protected $hidden = [
        'password',
    ];

    protected $fillable = [
        'nama',
        'hp',
        'wa',
        'bb',
        'email',
        'alamat',
        'tgl_masuk',
        'username',
        'password',
        'jenis'
    ];

    //relasi ke cs_main_project menggunakan pivot table cs_main_project_karyawan
    public function cs_main_projects()
    {
        return $this->belongsToMany(CsMainProject::class, 'cs_main_project_karyawan')
            ->withPivot('porsi');
    }
}
