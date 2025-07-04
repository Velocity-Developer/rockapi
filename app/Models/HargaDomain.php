<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HargaDomain extends Model
{
    // Nama tabel di database
    protected $table = 'tb_harga_domain';

    // tidak menggunakan timestamps
    public $timestamps = false;

    protected $fillable = [
        'bulan',
        'biaya'
    ];

    //append
    protected $appends = [
        'bulan_normalized',
        'biaya_normalized'
    ];

    //accessor
    public function getBulanNormalizedAttribute()
    {
        $map = [
            'januari' => '01',
            'februari' => '02',
            'maret' => '03',
            'april' => '04',
            'mei' => '05',
            'juni' => '06',
            'juli' => '07',
            'agustus' => '08',
            'september' => '09',
            'oktober' => '10',
            'november' => '11',
            'desember' => '12',
        ];

        $parts = explode(' ', strtolower(trim($this->bulan)));

        if (count($parts) != 2) return null;

        [$namaBulan, $tahun] = $parts;

        $bulan = $map[$namaBulan] ?? null;

        return $bulan ? "{$tahun}-{$bulan}" : null;
    }
    public function getBiayaNormalizedAttribute()
    {
        return $this->biaya ? (int) preg_replace('/[^0-9]/', '', $this->biaya) : 0;
    }
}
