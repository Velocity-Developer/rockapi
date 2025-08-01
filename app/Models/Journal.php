<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Journal extends Model
{
    protected $fillable = [
        'title',
        'description',
        'start',
        'end',
        'status',
        'priority',
        'user_id',
    ];

    //relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('start', Carbon::now()->month)
            ->whereYear('start', Carbon::now()->year);
    }
}
