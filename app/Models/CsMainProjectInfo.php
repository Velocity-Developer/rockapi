<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsMainProjectInfo extends Model
{
    protected $fillable = [
        'cs_main_project_id',
        'author_id',
        'jenis_project',
        'waktu_plus',
    ];

    //relasi ke CsMainProject
    public function cs_main_project()
    {
        return $this->belongsTo(CsMainProject::class, 'cs_main_project_id');
    }

    //relasi ke User
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
