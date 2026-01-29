<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Server extends Model
{
    protected $fillable = [
        'name',
        'type',
        'ip_address',
        'hostname',
        'port',
        'username',
        'password',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // hidden
    protected $hidden = [
        'password',
        'created_at',
        'updated_at',
    ];

    // Accessor untuk ambil password terdekripsi
    public function getRawPasswordAttribute(): ?string
    {
        return $this->password ? Crypt::decryptString($this->password) : null;
    }

    // Mutator untuk menyimpan password terenkripsi
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    // relasi ke tabel server_package
    public function server_package()
    {
        return $this->hasMany(ServerPackage::class, 'server_id');
    }

    // relasi ke tabel server_user
    public function server_user()
    {
        return $this->hasMany(ServerUser::class, 'server_package_id');
    }
}
