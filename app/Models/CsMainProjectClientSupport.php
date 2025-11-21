<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsMainProjectClientSupport extends Model
{
    protected $fillable = [
        'cs_main_project_id',
        'layanan',
        'tanggal',
        'user_id',
    ];

    //hidden
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    //cast
    protected $casts = [
        'cs_main_project_id' => 'integer',
        'user_id' => 'integer',
    ];

    //relasi ke CsMainProject
    public function cs_main_project()
    {
        return $this->belongsTo(CsMainProject::class, 'cs_main_project_id');
    }

    //relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
