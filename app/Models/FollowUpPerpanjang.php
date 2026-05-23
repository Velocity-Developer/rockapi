<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowUpPerpanjang extends Model
{

    // Nama tabel di database
    protected $table = 'follow_up_perpanjang';

    protected $fillable = [
        'status',
        'tanggal',
        'followup_terakhir',
        'whmcs_user_id',
        'whmcs_domain_id',
        'whmcs_hosting_id',
        'webhost_id',
        'user_id',
        'keterangan',
        'alasan',
    ];

    //relasi many ke tabel whmcs_user
    public function whmcsUser()
    {
        return $this->belongsTo(WhmcsUser::class, 'whmcs_user_id');
    }


    //relasi many ke tabel whmcs_domain
    public function whmcsDomain()
    {
        return $this->belongsTo(WhmcsDomain::class, 'whmcs_domain_id');
    }

    //relasi many ke tabel whmcs_hosting
    public function whmcsHosting()
    {
        return $this->belongsTo(WhmcsHosting::class, 'whmcs_hosting_id');
    }


    //relasi many ke tabel webhost
    public function webhost()
    {
        return $this->belongsTo(Webhost::class, 'webhost_id', 'id_webhost');
    }
    //relasi many ke tabel user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
