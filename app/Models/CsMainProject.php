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

    // tidak menggunakan timestamps
    public $timestamps = false;

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
    protected $appends = [
        'raw_dikerjakan',
    ];

    //accessor dikerjakan
    public function getRawDikerjakanAttribute()
    {
        $dikerjakan_oleh = $this->dikerjakan_oleh;
        if ($dikerjakan_oleh == null) {
            return null;
        }
        $data = explode(",", $dikerjakan_oleh);
        $result = [];
        foreach ($data as $item) {
            //jika kosong, skip
            if ($item == '') {
                continue;
            }
            if (preg_match('/^(\d+)\[(\d+)\]$/', $item, $matches)) {
                $result[] = (int) $matches[1];
            }
        }
        return $result;
    }

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

    //relasi one ke tabel pm_project
    public function pm_project()
    {
        return $this->hasOne(PmProject::class, 'id');
    }

    //relasi many ke tabel transaksi_masuk
    public function transaksi_masuk()
    {
        return $this->hasMany(TransaksiMasuk::class, 'id');
    }

    //relasi ke WmProject
    public function wm_project()
    {
        return $this->hasOne(WmProject::class, 'id');
    }

    //relasi many ke tabel cs_main_project_client_supports
    public function cs_main_project_client_supports()
    {
        return $this->hasMany(CsMainProjectClientSupport::class, 'cs_main_project_id');
    }

    //relasi one ke tabel cs_main_project_infos
    public function cs_main_project_info()
    {
        return $this->hasOne(CsMainProjectInfo::class, 'cs_main_project_id');
    }

    //relasi many to many ke Customer
    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_cs_main_project', 'cs_main_project_id', 'customer_id', 'id');
    }

    //relasi ke Invoice
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
