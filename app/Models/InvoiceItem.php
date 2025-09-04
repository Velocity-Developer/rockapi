<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'invoice_id',
        'nama',
        'jenis',
        'harga',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
    ];

    // Relasi ke invoice
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}