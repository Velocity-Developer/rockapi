<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalDetailSupport extends Model
{
    protected $fillable = [
        'journal_id',
        'hp',
        'wa',
        'email',
        'biaya',
        'tanggal_bayar',
    ];

    // relasi ke journal
    public function journal()
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }
}
