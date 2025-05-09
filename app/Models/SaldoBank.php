<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoBank extends Model
{
    // Nama tabel di database
    protected $table = 'tb_saldo_bank';

    //disable timestamps
    public $timestamps = false;

    protected $fillable = [
        'bulan',
        'bank',
        'nominal',
    ];
}
