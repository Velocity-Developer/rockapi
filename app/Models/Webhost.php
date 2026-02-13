<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhost extends Model
{
    // Nama tabel di database
    protected $table = 'tb_webhost';

    // Nama primary key yang tidak konvensional
    protected $primaryKey = 'id_webhost';

    // tidak menggunakan timestamps
    public $timestamps = false;

    protected $fillable = [
        'nama_web',
        'id_paket',
        'tgl_mulai',
        'id_server',
        'id_server2',
        'space',
        'space_use',
        'hp',
        'telegram',
        'hpads',
        'wa',
        'email',
        'tgl_exp',
        'tgl_update',
        'server_luar',
        'saldo',
        'kategori',
        'waktu',
        'staff',
        'via',
        'konfirmasi_order',
        'kata_kunci',
        'jenis_kelamin',
        'usia',
    ];

    // relasi one ke tabel paket
    public function paket()
    {
        return $this->belongsTo(Paket::class, 'id_paket');
    }

    // relasi many ke tabel cs_main_project
    public function csMainProjects()
    {
        return $this->hasMany(CsMainProject::class, 'id_webhost');
    }

    // relasi many ke tabel journal
    public function journals()
    {
        return $this->hasMany(Journal::class, 'webhost_id');
    }

    // relasi many to many ke Customer
    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_webhost', 'webhost_id', 'customer_id', 'id_webhost');
    }

    // relasi many ke tabel tb_followup_advertiser
    public function followup_advertiser()
    {
        return $this->hasOne(FollowupAdvertiser::class, 'id_webhost_ads')->withDefault([
            'id_webhost_ads' => null,
            'status_ads' => null,
        ]);
    }
}
