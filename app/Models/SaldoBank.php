<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoBank extends Model
{
    // Nama tabel di database
    protected $table = 'tb_saldo_bank';

    protected $fillable = [
        'bulan',
        'bank',
        'nominal',
    ];
}
