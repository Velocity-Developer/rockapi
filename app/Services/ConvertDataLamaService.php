<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\BiayaAds;

class ConvertDataLamaService
{
    protected array $bulanMap = [
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

    protected function normalizeBulan(string $bulanLama): ?string
    {
        $bulanLama = strtolower(trim($bulanLama));
        $parts = explode(' ', $bulanLama);
        if (count($parts) !== 2) return null;

        [$namaBulan, $tahun] = $parts;
        $bulan = $this->bulanMap[$namaBulan] ?? null;

        return $bulan ? "{$tahun}-{$bulan}" : null;
    }

    protected function normalizeBiaya(string $biayaLama): int
    {
        return (int) preg_replace('/[^0-9]/', '', $biayaLama);
    }

    protected function mapRows(array $rows, string $kategori): array
    {
        return array_map(function ($row) use ($kategori) {
            return [
                'bulan' => $this->normalizeBulan($row->bulan),
                'biaya' => $this->normalizeBiaya($row->biaya),
                'kategori' => $kategori,
            ];
        }, $rows);
    }

    public function handle_biaya_ads()
    {
        $ads  = DB::table('tb_biaya_ads')->select('bulan', 'biaya')->get()->all();
        $meta = DB::table('tb_biaya_ads_meta')->select('bulan', 'biaya')->get()->all();
        $am   = DB::table('tb_biaya_ads_am')->select('bulan', 'biaya')->get()->all();

        $combined = array_merge(
            $this->mapRows($ads, 'ads'),
            $this->mapRows($meta, 'meta'),
            $this->mapRows($am, 'am')
        );

        $normalized = collect($combined)->filter(
            fn($item) =>
            $item['bulan'] && $item['biaya'] > 0
        )->values();

        $result = [];
        foreach ($normalized as $row) {
            $biaya = BiayaAds::updateOrCreate(
                ['bulan' => $row['bulan'], 'kategori' => $row['kategori']],
                ['biaya' => $row['biaya']]
            );
            $result[] = $biaya;
        }

        return $result;
    }
}
