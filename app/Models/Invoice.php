<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit',
        'nama_klien',
        'alamat_klien',
        'webhost_id',
        'note',
        'status',
        'tanggal',
        'tanggal_bayar',
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

    /**
     * Boot method untuk menghasilkan nomor invoice secara otomatis
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            // Jika nomor invoice belum diisi
            if (empty($invoice->nomor)) {

                // Format: YYMMDD diambil dari tanggal invoice
                $today = $invoice->tanggal ? Carbon::parse($invoice->tanggal)->format('ymd') : date('ymd');

                // dapatkan nomor urut terakhir berdasarkan tanggal LIKE today%
                $lastInvoice = static::where('nomor', 'like', "{$today}%")
                    ->orderBy('nomor', 'desc')
                    ->first();

                if ($lastInvoice) {
                    // Ekstrak nomor urut dari invoice terakhir
                    $lastNumber = (int) substr($lastInvoice->nomor, -4);
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 1;
                }

                // Format nomor invoice: YYMMDD0001
                $invoice->nomor = $today . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
