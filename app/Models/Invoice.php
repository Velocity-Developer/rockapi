<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'nomor',
        'unit',
        'nama',
        'webhost_id',
        'note',
        'status',
        'tanggal',
        'tanggal_bayar',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_bayar' => 'date',
    ];

    // Relasi ke webhost
    public function webhost()
    {
        return $this->belongsTo(Webhost::class, 'webhost_id', 'id_webhost');
    }
    
    // Relasi ke invoice items
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
