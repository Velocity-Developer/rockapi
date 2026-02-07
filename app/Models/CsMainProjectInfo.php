<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class CsMainProjectInfo extends Model
{
    protected $fillable = [
        'cs_main_project_id',
        'author_id',
        'jenis_project',
        'waktu_plus',
    ];

    protected $appends = ['bobot'];

    //
    protected function bobot(): Attribute
    {
        return Attribute::get(
            function () {
                // Ambil jenis dari relasi cs_main_project
                $dikerjakan_oleh = $this->cs_main_project->dikerjakan_oleh ?? null;

                // Klasifikasi bobot berdasarkan jenis
                // Sesuaikan case dan value sesuai kebutuhan
                $nilaiBobot = 0;
                if (str_contains($dikerjakan_oleh, ',12')) {
                    $nilaiBobot = 2;
                } elseif (str_contains($dikerjakan_oleh, ',10')) {
                    $nilaiBobot = 0.3;
                }

                // Tambahkan dengan waktu_plus (jika ada)
                return $nilaiBobot + ($this->waktu_plus ?? 0);
            }
        );
    }

    // relasi ke CsMainProject
    public function cs_main_project()
    {
        return $this->belongsTo(CsMainProject::class, 'cs_main_project_id');
    }

    // relasi ke User
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
