<?php

namespace App\Services;

use App\Models\BiayaAds;
use App\Models\Konversi;
use Illuminate\Support\Facades\DB;

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
        if (count($parts) !== 2) {
            return null;
        }

        [$namaBulan, $tahun] = $parts;
        $bulan = $this->bulanMap[$namaBulan] ?? null;

        return $bulan ? "{$tahun}-{$bulan}" : null;
    }

    protected function normalizeBiaya(string $biayaLama): int
    {
        return (int) preg_replace('/[^0-9]/', '', $biayaLama);
    }

    protected function normalizeValue(?string $valueLama): ?float
    {
        if ($valueLama === null || $valueLama === '') {
            return null;
        }
        $cleaned = preg_replace('/[^0-9.]/', '', $valueLama);

        return $cleaned !== '' ? (float) $cleaned : null;
    }

    protected function mapKonversiRows(array $rows, string $kategori): array
    {
        return array_map(function ($row) use ($kategori) {
            return [
                'tanggal' => $row->date,
                'value' => $this->normalizeValue($row->value),
                'kategori' => $kategori,
            ];
        }, $rows);
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
        $ads = DB::table('tb_biaya_ads')->select('bulan', 'biaya')->get()->all();
        $meta = DB::table('tb_biaya_ads_meta')->select('bulan', 'biaya')->get()->all();
        $am = DB::table('tb_biaya_ads_am')->select('bulan', 'biaya')->get()->all();

        $combined = array_merge(
            $this->mapRows($ads, 'ads'),
            $this->mapRows($meta, 'meta'),
            $this->mapRows($am, 'am')
        );

        $normalized = collect($combined)->filter(
            fn ($item) => $item['bulan'] && $item['biaya'] > 0
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

    public function handle_konversi()
    {
        $konversi = DB::table('tb_konversi')->select('date', 'value')->get()->all();
        $display = DB::table('tb_konversi_display')->select('date', 'value')->get()->all();
        $wa5 = DB::table('tb_konversi_wa5')->select('date', 'value')->get()->all();
        $organik = DB::table('tb_konversi_organik')->select('date', 'value')->get()->all();

        $combined = array_merge(
            $this->mapKonversiRows($konversi, 'general'),
            $this->mapKonversiRows($display, 'display'),
            $this->mapKonversiRows($wa5, 'wa5'),
            $this->mapKonversiRows($organik, 'organik')
        );

        $filtered = collect($combined)->filter(
            fn ($item) => $item['tanggal'] && $item['value'] !== null
        )->values();

        $result = [];
        foreach ($filtered as $row) {
            $konversi = Konversi::updateOrCreate(
                ['tanggal' => $row['tanggal'], 'kategori' => $row['kategori']],
                ['value' => $row['value']]
            );
            $result[] = $konversi;
        }

        return $result;
    }
}
