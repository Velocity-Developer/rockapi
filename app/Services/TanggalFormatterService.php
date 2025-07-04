<?php

namespace App\Services;

use Carbon\Carbon;
use Locale;

class TanggalFormatterService
{
    public function toIndonesianMonthYear(string $date): string
    {
        $months = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];

        [$year, $month] = explode('-', $date);
        return ($months[$month] ?? $month) . ' ' . $year;
    }
}
