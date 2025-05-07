<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
* model 'Bank'
* untuk tabel 'tb_bank'
* mencatat transaksi bank dengan relasi ke tabel 'tb_cs_main_project' dari kolom 'jenis'
* agar bisa dibuatkan relasi ke tabel 'tb_cs_main_project' dibuatkan pivot table 'bank_cs_main_project'
* dengan kolom 'bank_id' dan 'cs_main_project_id'
* relasi one ke tabel 'tb_webhost' dari kolom 'id_webhost'
* 
* jalankan seeder 'BankJenisSeeder' untuk membuat data pivot
*/

class Bank extends Model
{
    // Nama tabel di database
    protected $table = 'tb_bank';

    //disable timestamps
    public $timestamps = false;

    protected $appends = ['jenis_array'];

    protected $fillable = [
        'bank',
        'tgl',
        'jenis',
        'keterangan_bank',
        'jenis_transaksi',
        'nominal',
        'id_webhost',
        'status'
    ];

    protected $casts = [
        'nominal'   => 'integer',
    ];

    //accessor jenis
    public function getJenisArrayAttribute()
    {
        //jika jenis null / kosong, return array kosong
        if (!$this->jenis) {
            return [];
        }

        //unserialize jenis
        $jenis = unserialize($this->attributes['jenis']);

        //loop
        $results = [];
        foreach ($jenis as $key => $value) {
            $jns = $value ? explode('-', $value) : '';
            $results[] = $jns[1];
        }

        return $results;
    }

    //relasi ke CsMainProject
    public function CsMainProject()
    {
        return $this->belongsToMany(CsMainProject::class, 'bank_cs_main_project');
    }

    //relasi ke TransaksiKeluar
    public function TransaksiKeluar()
    {
        return $this->belongsToMany(
            TransaksiKeluar::class,
            'bank_transaksi_keluar',
            'bank_id',              // foreign pivot key
            'transaksi_keluar_id',  // related pivot key
            'id',                   // local key di tb_cs_main_project
            'id_transaksi_keluar'   // local key di tb_transaksi_keluar
        );
    }

    //relasi one ke tabel webhost
    public function webhost()
    {
        return $this->belongsTo(Webhost::class, 'id_webhost');
    }
}
