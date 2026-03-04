<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhmcsDomain extends Model
{
    protected $fillable = [
        'whmcs_id',
        'domain',
        'expirydate',
        'registrationdate',
        'nextduedate',
        'type',
        'status',
        'registrar',
    ];
}
