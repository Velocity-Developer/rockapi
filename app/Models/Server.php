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
        'options',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
    ];

    // Accessor untuk ambil password terdekripsi
    public function getPasswordAttribute(): ?string
    {
        return $this->password ? Crypt::decryptString($this->password) : null;
    }

    // Mutator untuk menyimpan password terenkripsi
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }
}
