<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapFormsLogKonversi extends Model
{
    protected $fillable = [
        'rekap_form_id',
        'kirim_konversi_id',
        'jobid',
        'conversion_action_id',
    ];

    /**
     * Get the rekap form that owns the log konversi.
     */
    public function rekapForm(): BelongsTo
    {
        return $this->belongsTo(RekapForm::class);
    }
}
