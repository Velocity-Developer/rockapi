<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankSorting extends Model
{
    // Nama tabel di database
    protected $table = 'tb_bank_sorting';

    // disable timestamps
    public $timestamps = false;

    protected $fillable = [
        'bank',
        'bulan',
        'sorting',
    ];

    protected $appends = ['sorting_array'];

    // accessor sorting_array
    public function getSortingArrayAttribute()
    {
        return explode(',', $this->sorting);
    }
}
