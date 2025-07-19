<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerPackage extends Model
{
    protected $fillable = [
        'server_id',
        'name',
        'bandwidth',
        'email_daily_limit',
        'inode',
        'quota',
    ];

    //relasi one ke tabel server
    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }
}
