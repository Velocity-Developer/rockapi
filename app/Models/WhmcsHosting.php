<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhmcsHosting extends Model
{
    protected $fillable = [
        'whmcs_id',
        'whmcs_userid',
        'domain',
        'nextduedate',
        'billingcycle',
        'domainstatus',
        'package_name',
        'package_servertype',
        'package_name_id'
    ];

    // relasi ke tabel webhost
    public function webhost()
    {
        return $this->belongsTo(Webhost::class, 'domain', 'nama_web');
    }

    // relasi ke tabel whmcs_user
    public function whmcs_user()
    {
        return $this->belongsTo(WhmcsUser::class, 'whmcs_userid', 'whmcs_id');
    }

    // relasi ke tabel whmcs_domain
    public function domain()
    {
        return $this->belongsTo(WhmcsDomain::class, 'domain', 'domain');
    }
}
