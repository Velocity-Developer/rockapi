<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start',
        'end',
        'status',
        'priority',
        'user_id',
        'webhost_id',
        'cs_main_project_id',
        'journal_category_id'
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
