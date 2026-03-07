<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhmcsUser extends Model
{
    protected $fillable = [
        'whmcs_id',
        'email',
        'firstname',
        'lastname',
    ];

    //relasi one to many dengan whmcs_domain
    public function domains()
    {
        return $this->hasMany(WhmcsDomain::class, 'whmcs_userid', 'whmcs_id');
    }

    //relasi one to many dengan whmcs_webhosting
    public function hostings()
    {
        return $this->hasMany(WhmcsHosting::class, 'whmcs_userid', 'whmcs_id');
    }
}
