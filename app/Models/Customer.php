<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    //
    protected $fillable = ['nama', 'email', 'hp', 'alamat'];

    //relasi ke invoice
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
