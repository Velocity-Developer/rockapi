<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReminderCS extends Model
{
    // Nama tabel di database
    protected $table = 'reminder_cs';

    protected $fillable = [
        'jam',
        'keterangan',
        'user_id',
    ];

    // relasi many ke tabel user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
