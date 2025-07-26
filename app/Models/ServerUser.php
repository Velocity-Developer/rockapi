<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerUser extends Model
{
    protected $fillable = [
        'username',
        'server_id',
        'cron',
        'domain',
        'domains',
        'ip',
        'lets_encrypt',
        'name',
        'ns1',
        'ns2',
        'package',
        'server_package_id',
        'quotaLim',
        'php',
        'spam',
        'ssh',
        'ssl',
        'suspended',
        'user_type',
        'users',
        'wordpress',
    ];

    protected $casts = [
        'cron'      => 'boolean',
        'php'       => 'boolean',
        'spam'      => 'boolean',
        'ssh'       => 'boolean',
        'ssl'       => 'boolean',
        'suspended' => 'boolean',
        'wordpress' => 'boolean',
        'domains'   => 'array',
        'users'     => 'array',
    ];

    //relasi ke tabel server
    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    //relasi ke tabel server_package,
    public function server_package()
    {
        return $this->belongsTo(ServerPackage::class, 'server_package_id');
    }
}
