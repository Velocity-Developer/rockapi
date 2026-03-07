<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhmcsDomain extends Model
{
    protected $fillable = [
        'whmcs_id',
        'whmcs_userid',
        'domain',
        'expirydate',
        'registrationdate',
        'nextduedate',
        'type',
        'status',
        'registrar',
        'user_email'
    ];

    // relasi ke tabel webhost
    public function webhost()
    {
        return $this->belongsTo(Webhost::class, 'domain', 'nama_web');
    }

    // relasi ke tabel whmcs_user
    public function user()
    {
        return $this->belongsTo(WhmcsUser::class, 'whmcs_userid', 'whmcs_id');
    }

    // relasi ke tabel whmcs_hosting
    public function hosting()
    {
        return $this->belongsTo(WhmcsHosting::class, 'domain', 'domain');
    }
}
