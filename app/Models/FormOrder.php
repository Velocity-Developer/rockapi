<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormOrder extends Model
{
    protected $fillable = [
        'source',
        'nama',
        'hp',
        'usia',
        'kebutuhan',
    ];

    protected function casts(): array
    {
        return [
            'usia' => 'integer',
        ];
    }
}
