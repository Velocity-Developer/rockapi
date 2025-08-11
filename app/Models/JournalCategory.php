<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'role',
        'icon'
    ];

    //sembunyikan
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
