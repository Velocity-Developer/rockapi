<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhostSubscription extends Model
{
    protected $fillable = [
        'webhost_id',
        'cs_main_project_id',
        'parent_subscription_id',
        'source_type',
        'service_type',
        'start_date',
        'end_date',
        'renewed_from_date',
        'status',
        'nominal',
        'description',
        'payment_status',
        'paid_at',
        'provider_status',
        'provider_expiry_date',
        'is_whmcs_mismatch',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'renewed_from_date' => 'date',
        'paid_at' => 'date',
        'provider_expiry_date' => 'date',
        'is_whmcs_mismatch' => 'boolean',
        'nominal' => 'decimal:2',
    ];

    public function webhost()
    {
        return $this->belongsTo(Webhost::class, 'webhost_id', 'id_webhost');
    }

    public function csMainProject()
    {
        return $this->belongsTo(CsMainProject::class, 'cs_main_project_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_subscription_id');
    }

    public function renewals()
    {
        return $this->hasMany(self::class, 'parent_subscription_id');
    }
}
