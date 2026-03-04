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
}
