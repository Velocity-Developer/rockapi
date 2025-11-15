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
        'customer_id',
        'cs_main_project_id',
        'note',
        'status',
        'subtotal',
        'pajak',
        'nama_pajak',
        'nominal_pajak',
        'total',
        'tanggal',
        'jatuh_tempo',
        'tanggal_bayar',
    ];

    protected $casts = [
        'pajak' => 'boolean',
    ];

    protected $appends = [
        'url_pdf_preview',
        'url_pdf_download',
    ];

    // Relasi ke customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Relasi ke cs main project
    public function cs_main_project()
    {
        return $this->belongsTo(CsMainProject::class, 'cs_main_project_id');
    }

    // Relasi ke invoice items
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    //accessor url_pdf_preview
    public function getUrlPdfPreviewAttribute()
    {
        return url("/api/invoice/{$this->id}/pdf");
    }

    //accessor url_pdf_download
    public function getUrlPdfDownloadAttribute()
    {
        return url("/api/invoice/{$this->id}/pdf?download=true");
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

                // Ambil unit dan jadikan uppercase
                $unitPrefix = strtoupper($invoice->unit ?? 'VDI');

                // dapatkan nomor urut terakhir berdasarkan unit dan tanggal LIKE unitPrefix-today%
                $lastInvoice = static::where('nomor', 'like', "{$unitPrefix}{$today}%")
                    ->orderBy('nomor', 'desc')
                    ->first();

                if ($lastInvoice) {
                    // Ekstrak nomor urut dari invoice terakhir
                    $lastNumber = (int) substr($lastInvoice->nomor, -4);
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 1;
                }

                // Format nomor invoice: UNITYYMMDD0001
                $invoice->nomor = $unitPrefix . $today . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
