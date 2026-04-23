<?php

namespace App\Http\Controllers\Laporan;

use App\Helpers\TanggalFormatterHelper;
use App\Http\Controllers\Controller;
use App\Models\BiayaAds;
use App\Models\CsMainProject;
use App\Models\HargaDomain;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RincianTransaksiController extends Controller
{
    private array $pembuatanJenis = [
        'Pembuatan',
        'Pembuatan apk',
        'Pembuatan apk custom',
        'Pembuatan web konsep',
        'Pembuatan Tanpa Domain',
        'Pembuatan Tanpa Hosting',
        'Pembuatan Tanpa Domain+Hosting',
    ];

    public function index(Request $request)
    {
        $formatter = new TanggalFormatterHelper;
        $dari = $request->query('dari')
            ? Carbon::parse($request->query('dari'))->startOfMonth()
            : now()->subMonths(5)->startOfMonth();
        $sampai = $request->query('sampai')
            ? Carbon::parse($request->query('sampai'))->endOfMonth()
            : now()->endOfMonth();

        $tables = $this->tables($dari, $sampai, $formatter);

        return response()->json([
            'dari' => $dari->format('Y-m-d'),
            'sampai' => $sampai->format('Y-m-d'),
            'months' => $this->months($dari, $sampai, $formatter),
            'summary' => $this->summary($dari, $sampai, $formatter),
            'tables' => $tables,
        ]);
    }

    private function tables(Carbon $dari, Carbon $sampai, TanggalFormatterHelper $formatter): array
    {
        $moneyColumns = [
            ['field' => 'bulan', 'header' => 'Bulan', 'type' => 'text'],
            ['field' => 'jumlah_web', 'header' => 'Jumlah Web', 'type' => 'number'],
            ['field' => 'nominal', 'header' => 'Nominal', 'type' => 'money'],
        ];

        $domainColumns = [
            ...$moneyColumns,
            ['field' => 'domain', 'header' => 'Domain', 'type' => 'money'],
            ['field' => 'profit', 'header' => 'Profit Kotor', 'type' => 'money'],
            ['field' => 'biaya_iklan', 'header' => 'Biaya Iklan', 'type' => 'money'],
            ['field' => 'net_profit', 'header' => 'Net Profit', 'type' => 'money'],
        ];

        $perpanjanganColumns = [
            ...$moneyColumns,
            ['field' => 'domain', 'header' => 'Domain', 'type' => 'money'],
            ['field' => 'profit', 'header' => 'Nett Profit', 'type' => 'money'],
        ];

        return [
            [
                'name' => 'Pembuatan',
                'columns' => $domainColumns,
                'rows' => $this->rincian($this->pembuatanJenis, 150000, '', 'Pembuatan', $dari, $sampai, $formatter),
                'wide' => true,
            ],
            [
                'name' => 'Perpanjangan',
                'columns' => $perpanjanganColumns,
                'rows' => $this->rincian(['Perpanjangan'], 0, '', 'Perpanjangan', $dari, $sampai, $formatter),
            ],
            [
                'name' => 'Tambah Space',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Tambah Space'], 0, '', 'Tambah Space', $dari, $sampai, $formatter),
            ],
            [
                'name' => 'Jasa Update Web',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Jasa Update Web'], 0, '', 'Jasa Update Web', $dari, $sampai, $formatter),
            ],
            [
                'name' => 'Lain - lain',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Lain-lain', 'Lain - Lain'], 0, '', 'Lain - lain', $dari, $sampai, $formatter),
            ],
            [
                'name' => 'Iklan + Deposit Google (Semua HP)',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Iklan Google', 'Deposit Iklan Google'], 0, 'semuahp', 'Iklan + Deposit Google (Semua HP)', $dari, $sampai, $formatter),
            ],
            [
                'name' => 'Iklan+ Deposit Google (Bukan HP Ads)',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Iklan Google', 'Deposit Iklan Google'], 0, 'bukanhpads', 'Iklan + Deposit Google (Bukan HP Ads)', $dari, $sampai, $formatter),
            ],
            [
                'name' => 'Iklan Google (HP Ads)',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Iklan Google'], 0, 'hpads', 'Iklan Google (HP Ads)', $dari, $sampai, $formatter),
            ],
            [
                'name' => 'Jasa update iklan google',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Jasa update iklan google', ' Jasa update iklan google'], 0, 'all', 'Jasa update iklan google', $dari, $sampai, $formatter),
            ],
            [
                'name' => 'Deposit Iklan Google (HP Ads)',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Deposit Iklan Google'], 0, 'hpads', 'Deposit Iklan Google (HP Ads)', $dari, $sampai, $formatter),
            ],
            [
                'name' => 'Folowup Advertiser Pembuatan',
                'columns' => [
                    ['field' => 'bulan', 'header' => 'Bulan', 'type' => 'text'],
                    ['field' => 'jumlah_web', 'header' => 'Jumlah Web', 'type' => 'number'],
                ],
                'rows' => $this->followup($dari, $sampai, $formatter),
            ],
            [
                'name' => 'Pembuatan Web Konsep',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Pembuatan web konsep'], 150000, '', 'Pembuatan', $dari, $sampai, $formatter),
            ],
            [
                'name' => 'Omzet',
                'columns' => $moneyColumns,
                'rows' => $this->rincian('all', 0, 'semuahp', 'Omzet', $dari, $sampai, $formatter),
            ],
            [
                'name' => 'AM Dini',
                'columns' => $moneyColumns,
                'rows' => $this->rincian('all', 0, 'semuahp', 'AM', $dari, $sampai, $formatter),
            ],
        ];
    }

    private function rincian(array|string $jenisData, int $minimal, string $jenisFilter, string $namaTabel, Carbon $dari, Carbon $sampai, TanggalFormatterHelper $formatter, ?int $tglDari = null, ?int $tglSampai = null): array
    {
        $query = CsMainProject::with('webhost:id_webhost,nama_web,hpads')
            ->whereDate('tgl_masuk', '>=', $dari->format('Y-m-d'))
            ->whereDate('tgl_masuk', '<=', $sampai->format('Y-m-d'))
            ->where('biaya', '>', $minimal);

        if ($tglDari !== null && $tglSampai !== null) {
            $query->whereDay('tgl_masuk', '>=', $tglDari)
                ->whereDay('tgl_masuk', '<=', $tglSampai);
        }

        if ($jenisData !== 'all') {
            $query->whereIn('jenis', $jenisData);
        }

        if ($jenisFilter === 'hpads') {
            $query->whereHas('webhost', fn ($webhost) => $webhost->where('hpads', 'ya'));
        }

        if ($namaTabel === 'AM') {
            $query->where('staff', 'Dini');
        }

        $grouped = $query->get()->groupBy(
            fn ($item) => Carbon::parse($item->tgl_masuk)->format('Y-m')
        )->sortKeysDesc();

        return $grouped->map(function ($items, $month) use ($formatter, $namaTabel, $tglDari, $tglSampai) {
            $label = $formatter->toIndonesianMonthYear($month);
            $jumlahWeb = $items->count();
            $nominal = (int) $items->sum('biaya');
            $hargaDomain = $this->domainPrice($label);
            $domain = in_array($namaTabel, ['Pembuatan', 'Perpanjangan'], true) ? $jumlahWeb * $hargaDomain : 0;
            $profit = $nominal - $domain;
            $biayaIklan = $namaTabel === 'Pembuatan' && $jumlahWeb > 0
                ? $this->adsPrice($month, $tglDari, $tglSampai)
                : 0;

            return [
                'key' => $month,
                'bulan' => $label,
                'tanggal' => ($tglDari !== null && $tglSampai !== null) ? $this->tanggalLabel($tglDari, $tglSampai) : null,
                'jumlah_web' => $jumlahWeb,
                'nominal' => $nominal,
                'harga_domain' => $hargaDomain,
                'domain' => $domain,
                'profit' => $profit,
                'biaya_iklan' => $biayaIklan,
                'net_profit' => $profit - $biayaIklan,
            ];
        })->values()->all();
    }

    private function summary(Carbon $dari, Carbon $sampai, TanggalFormatterHelper $formatter): array
    {
        $categories = [
            'Pembuatan' => ['rows' => $this->rincian($this->pembuatanJenis, 150000, '', 'Pembuatan', $dari, $sampai, $formatter), 'field' => 'net_profit'],
            'Perpanjangan' => ['rows' => $this->rincian(['Perpanjangan'], 0, '', 'Perpanjangan', $dari, $sampai, $formatter), 'field' => 'profit'],
            'Tambah Space' => ['rows' => $this->rincian(['Tambah Space'], 0, '', 'Tambah Space', $dari, $sampai, $formatter), 'field' => 'nominal'],
            'Jasa Update Web' => ['rows' => $this->rincian(['Jasa Update Web'], 0, '', 'Jasa Update Web', $dari, $sampai, $formatter), 'field' => 'nominal'],
            'Lain - lain' => ['rows' => $this->rincian(['Lain-lain', 'Lain - Lain'], 0, '', 'Lain - lain', $dari, $sampai, $formatter), 'field' => 'nominal'],
            'Iklan + Deposit Google (Semua HP)' => ['rows' => $this->rincian(['Iklan Google', 'Deposit Iklan Google'], 0, 'semuahp', 'Iklan + Deposit Google (Semua HP)', $dari, $sampai, $formatter), 'field' => 'nominal'],
            'Jasa update iklan google' => ['rows' => $this->rincian(['Jasa update iklan google'], 0, '', 'Jasa update iklan google', $dari, $sampai, $formatter), 'field' => 'nominal'],
        ];

        $indexed = collect($categories)->map(fn ($config) => collect($config['rows'])->keyBy('key'));
        $rows = [];

        foreach ($this->months($dari, $sampai, $formatter) as $index => $month) {
            $row = [
                'key' => $month['key'],
                'bulan' => $month['label'],
                'pembuatan' => 0,
                'perpanjangan' => 0,
                'tambah_space' => 0,
                'jasa_update_web' => 0,
                'lain_lain' => 0,
                'iklan_deposit_google' => 0,
                'jasa_update_iklan_google' => 0,
                'omzet' => 0,
                'prediksi' => null,
            ];

            foreach ($categories as $label => $config) {
                $value = (int) ($indexed[$label][$month['key']][$config['field']] ?? 0);
                $field = match ($label) {
                    'Pembuatan' => 'pembuatan',
                    'Perpanjangan' => 'perpanjangan',
                    'Tambah Space' => 'tambah_space',
                    'Jasa Update Web' => 'jasa_update_web',
                    'Lain - lain' => 'lain_lain',
                    'Iklan + Deposit Google (Semua HP)' => 'iklan_deposit_google',
                    default => 'jasa_update_iklan_google',
                };
                $row[$field] = $value;
                $row['omzet'] += $value;
            }

            if ($index === 0 && now()->day > 0) {
                $row['prediksi'] = (int) round(($row['omzet'] / now()->day) * now()->daysInMonth);
            }

            $rows[] = $row;
        }

        return [
            'columns' => [
                ['field' => 'bulan', 'header' => 'Bulan', 'type' => 'text'],
                ['field' => 'pembuatan', 'header' => 'Pembuatan', 'type' => 'money'],
                ['field' => 'perpanjangan', 'header' => 'Perpanjangan', 'type' => 'money'],
                ['field' => 'tambah_space', 'header' => 'Tambah Space', 'type' => 'money'],
                ['field' => 'jasa_update_web', 'header' => 'Jasa Update Web', 'type' => 'money'],
                ['field' => 'lain_lain', 'header' => 'Lain - lain', 'type' => 'money'],
                ['field' => 'iklan_deposit_google', 'header' => 'Iklan + Deposit Google (Semua HP)', 'type' => 'money'],
                ['field' => 'jasa_update_iklan_google', 'header' => 'Jasa update iklan google', 'type' => 'money'],
                ['field' => 'omzet', 'header' => 'Omzet', 'type' => 'money'],
            ],
            'rows' => $rows,
        ];
    }

    private function followup(Carbon $dari, Carbon $sampai, TanggalFormatterHelper $formatter): array
    {
        return CsMainProject::query()
            ->join('tb_webhost', 'tb_cs_main_project.id_webhost', '=', 'tb_webhost.id_webhost')
            ->join('tb_followup_advertiser', 'tb_followup_advertiser.id_webhost_ads', '=', 'tb_webhost.id_webhost')
            ->whereDate('tb_cs_main_project.tgl_masuk', '>=', $dari->format('Y-m-d'))
            ->whereDate('tb_cs_main_project.tgl_masuk', '<=', $sampai->format('Y-m-d'))
            ->whereIn('tb_cs_main_project.jenis', $this->pembuatanJenis)
            ->where('tb_followup_advertiser.status_ads', 'Sudah iklan')
            ->select('tb_cs_main_project.*')
            ->get()
            ->groupBy(fn ($item) => Carbon::parse($item->tgl_masuk)->format('Y-m'))
            ->sortKeysDesc()
            ->map(fn ($items, $month) => [
                'key' => $month,
                'bulan' => $formatter->toIndonesianMonthYear($month),
                'jumlah_web' => $items->count(),
            ])
            ->values()
            ->all();
    }

    private function months(Carbon $dari, Carbon $sampai, TanggalFormatterHelper $formatter): array
    {
        $period = CarbonPeriod::create($dari->copy()->startOfMonth(), '1 month', $sampai->copy()->startOfMonth());

        return collect($period)
            ->map(fn (Carbon $date) => [
                'key' => $date->format('Y-m'),
                'label' => $formatter->toIndonesianMonthYear($date->format('Y-m')),
            ])
            ->reverse()
            ->values()
            ->all();
    }

    private function domainPrice(string $bulan): int
    {
        $harga = HargaDomain::where('bulan', $bulan)->first()
            ?? HargaDomain::orderByDesc('id')->first();

        return $harga ? $harga->biaya_normalized : 0;
    }

    private function adsPrice(string $month, ?int $tglDari = null, ?int $tglSampai = null): int
    {
        if ($tglDari === null || $tglSampai === null) {
            return (int) (BiayaAds::where('bulan', $month)
                ->where('kategori', 'ads')
                ->sum('biaya') ?? 0);
        }

        [$dateFrom, $dateTo] = $this->adsDateRange($month, $tglDari, $tglSampai);

        if (! $dateFrom || ! $dateTo) {
            return 0;
        }

        return $this->fetchAdsCostRange($dateFrom, $dateTo);
    }

    private function adsDateRange(string $month, int $tglDari, int $tglSampai): array
    {
        $date = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $startDay = max(1, min($tglDari, $date->daysInMonth));
        $endDay = max(1, min($tglSampai, $date->daysInMonth));

        if ($endDay < $startDay) {
            return [null, null];
        }

        return [
            $date->copy()->day($startDay)->format('Y-m-d'),
            $date->copy()->day($endDay)->format('Y-m-d'),
        ];
    }

    private function fetchAdsCostRange(string $dateFrom, string $dateTo): int
    {
        $cacheKey = 'rincian_transaksi_ads_cost_'.md5($dateFrom.'|'.$dateTo);

        return (int) Cache::remember($cacheKey, now()->addHours(6), function () use ($dateFrom, $dateTo) {
            try {
                $response = Http::timeout(6)
                    ->connectTimeout(3)
                    ->withHeaders([
                        'Referer' => 'https://velocitydeveloper.net/index.php?pg=rincian_transaksi_tgl',
                    ])
                    ->get('https://api.velocitydeveloper.com/ads_metrics_range.php', [
                        'key' => 'hutara000',
                        'campaign_id' => 1019866753,
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                    ]);

                if (! $response->ok()) {
                    return 0;
                }

                return (int) round((float) ($response->json('cost') ?? 0));
            } catch (\Throwable) {
                return 0;
            }
        });
    }

    public function rincian_tanggal(Request $request)
    {
        $formatter = new TanggalFormatterHelper;
        $dari = $request->query('dari')
            ? Carbon::parse($request->query('dari'))->startOfMonth()
            : now()->subMonths(12)->startOfMonth();
        $sampai = $request->query('sampai')
            ? Carbon::parse($request->query('sampai'))->endOfMonth()
            : now()->endOfMonth();
        $tglDari = max(1, min(31, (int) ($request->query('tgl_dari', $request->query('tgl-dari', 1)))));
        $tglSampai = max(1, min(31, (int) ($request->query('tgl_sampai', $request->query('tgl-sampai', now()->day)))));

        return response()->json([
            'dari' => $dari->format('Y-m-d'),
            'sampai' => $sampai->format('Y-m-d'),
            'tgl_dari' => $tglDari,
            'tgl_sampai' => $tglSampai,
            'months' => $this->months($dari, $sampai, $formatter),
            'summary' => $this->summaryTanggal($dari, $sampai, $tglDari, $tglSampai, $formatter),
            'tables' => $this->tablesTanggal($dari, $sampai, $tglDari, $tglSampai, $formatter),
        ]);
    }

    private function tablesTanggal(Carbon $dari, Carbon $sampai, int $tglDari, int $tglSampai, TanggalFormatterHelper $formatter): array
    {
        $moneyColumns = [
            ['field' => 'bulan', 'header' => 'Bulan', 'type' => 'text'],
            ['field' => 'jumlah_web', 'header' => 'Jumlah Web', 'type' => 'number'],
            ['field' => 'nominal', 'header' => 'Nominal', 'type' => 'money'],
        ];

        $transactionColumns = [
            ['field' => 'bulan', 'header' => 'Bulan', 'type' => 'text'],
            ['field' => 'jumlah_web', 'header' => 'Transaksi Masuk', 'type' => 'number'],
            ['field' => 'nominal', 'header' => 'Nominal', 'type' => 'money'],
        ];

        return [
            [
                'name' => 'Pembuatan',
                'columns' => $moneyColumns,
                'rows' => $this->rincian($this->pembuatanJenis, 150000, '', 'Pembuatan', $dari, $sampai, $formatter, $tglDari, $tglSampai),
                'wide' => false,
            ],
            [
                'name' => 'Perpanjangan',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Perpanjangan'], 0, '', 'Perpanjangan', $dari, $sampai, $formatter, $tglDari, $tglSampai),
            ],
            [
                'name' => 'Tambah Space',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Tambah Space'], 0, '', 'Tambah Space', $dari, $sampai, $formatter, $tglDari, $tglSampai),
            ],
            [
                'name' => 'Jasa Update Web',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Jasa Update Web'], 0, '', 'Jasa Update Web', $dari, $sampai, $formatter, $tglDari, $tglSampai),
            ],
            [
                'name' => 'Lain - lain',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Lain-lain', 'Lain - Lain'], 0, '', 'Lain - lain', $dari, $sampai, $formatter, $tglDari, $tglSampai),
            ],
            [
                'name' => 'Iklan + Deposit Google (Semua HP)',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Iklan Google', 'Deposit Iklan Google'], 0, 'semuahp', 'Iklan + Deposit Google (Semua HP)', $dari, $sampai, $formatter, $tglDari, $tglSampai),
            ],
            [
                'name' => 'Iklan+ Deposit Google (Bukan HP Ads)',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Iklan Google', 'Deposit Iklan Google'], 0, 'bukanhpads', 'Iklan + Deposit Google (Bukan HP Ads)', $dari, $sampai, $formatter, $tglDari, $tglSampai),
            ],
            [
                'name' => 'Iklan Google (HP Ads)',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Iklan Google'], 0, 'hpads', 'Iklan Google (HP Ads)', $dari, $sampai, $formatter, $tglDari, $tglSampai),
            ],
            [
                'name' => 'Jasa update iklan google',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Jasa update iklan google', ' Jasa update iklan google'], 0, 'all', 'Jasa update iklan google', $dari, $sampai, $formatter, $tglDari, $tglSampai),
            ],
            [
                'name' => 'Deposit Iklan Google (HP Ads)',
                'columns' => $moneyColumns,
                'rows' => $this->rincian(['Deposit Iklan Google'], 0, 'hpads', 'Deposit Iklan Google (HP Ads)', $dari, $sampai, $formatter, $tglDari, $tglSampai),
            ],
            [
                'name' => 'Omset',
                'columns' => $transactionColumns,
                'rows' => $this->rincian('all', 0, 'semuahp', 'Omzet', $dari, $sampai, $formatter, $tglDari, $tglSampai),
            ],
            [
                'name' => 'AM Dini',
                'columns' => $transactionColumns,
                'rows' => $this->rincian('all', 0, 'semuahp', 'AM', $dari, $sampai, $formatter, $tglDari, $tglSampai),
            ],
        ];
    }

    private function summaryTanggal(Carbon $dari, Carbon $sampai, int $tglDari, int $tglSampai, TanggalFormatterHelper $formatter): array
    {
        $categories = [
            'Pembuatan' => ['rows' => $this->rincian($this->pembuatanJenis, 150000, '', 'Pembuatan', $dari, $sampai, $formatter, $tglDari, $tglSampai), 'field' => 'net_profit'],
            'Perpanjangan' => ['rows' => $this->rincian(['Perpanjangan'], 150000, '', 'Perpanjangan', $dari, $sampai, $formatter, $tglDari, $tglSampai), 'field' => 'net_profit'],
            'Tambah Space' => ['rows' => $this->rincian(['Tambah Space'], 0, '', 'Tambah Space', $dari, $sampai, $formatter, $tglDari, $tglSampai), 'field' => 'nominal'],
            'Jasa Update Web' => ['rows' => $this->rincian(['Jasa Update Web'], 0, '', 'Jasa Update Web', $dari, $sampai, $formatter, $tglDari, $tglSampai), 'field' => 'nominal'],
            'Lain - lain' => ['rows' => $this->rincian(['Lain-lain', 'Lain - Lain'], 0, '', 'Lain - lain', $dari, $sampai, $formatter, $tglDari, $tglSampai), 'field' => 'nominal'],
            'Iklan + Deposit Google (Semua HP)' => ['rows' => $this->rincian(['Iklan Google', 'Deposit Iklan Google'], 0, 'semuahp', 'Iklan + Deposit Google (Semua HP)', $dari, $sampai, $formatter, $tglDari, $tglSampai), 'field' => 'nominal'],
            'Jasa update iklan google' => ['rows' => $this->rincian(['Jasa update iklan google', ' Jasa update iklan google'], 0, '', 'Jasa update iklan google', $dari, $sampai, $formatter, $tglDari, $tglSampai), 'field' => 'nominal'],
        ];

        $indexed = collect($categories)->map(fn ($config) => collect($config['rows'])->keyBy('key'));
        $rows = [];

        foreach ($this->months($dari, $sampai, $formatter) as $index => $month) {
            $row = [
                'key' => $month['key'],
                'bulan' => $month['label'],
                'tanggal' => $this->tanggalLabel($tglDari, $tglSampai),
                'pembuatan' => 0,
                'biaya_iklan' => (int) ($indexed['Pembuatan'][$month['key']]['biaya_iklan'] ?? 0),
                'perpanjangan' => 0,
                'tambah_space' => 0,
                'jasa_update_web' => 0,
                'lain_lain' => 0,
                'iklan_deposit_google' => 0,
                'jasa_update_iklan_google' => 0,
                'omzet' => 0,
                'prediksi' => null,
            ];

            foreach ($categories as $label => $config) {
                $value = (int) ($indexed[$label][$month['key']][$config['field']] ?? 0);
                $field = match ($label) {
                    'Pembuatan' => 'pembuatan',
                    'Perpanjangan' => 'perpanjangan',
                    'Tambah Space' => 'tambah_space',
                    'Jasa Update Web' => 'jasa_update_web',
                    'Lain - lain' => 'lain_lain',
                    'Iklan + Deposit Google (Semua HP)' => 'iklan_deposit_google',
                    default => 'jasa_update_iklan_google',
                };
                $row[$field] = $value;
                $row['omzet'] += $value;
            }

            if ($index === 0 && $tglSampai > 0) {
                $row['prediksi'] = (int) round(($row['omzet'] / $tglSampai) * now()->daysInMonth);
            }

            $rows[] = $row;
        }

        return [
            'columns' => [
                ['field' => 'bulan', 'header' => 'Bulan', 'type' => 'text'],
                ['field' => 'tanggal', 'header' => 'Tanggal', 'type' => 'text'],
                ['field' => 'pembuatan', 'header' => 'Pembuatan', 'type' => 'money'],
                ['field' => 'biaya_iklan', 'header' => 'Biaya Iklan', 'type' => 'money'],
                ['field' => 'perpanjangan', 'header' => 'Perpanjangan', 'type' => 'money'],
                ['field' => 'tambah_space', 'header' => 'Tambah Space', 'type' => 'money'],
                ['field' => 'jasa_update_web', 'header' => 'Jasa Update Web', 'type' => 'money'],
                ['field' => 'lain_lain', 'header' => 'Lain - lain', 'type' => 'money'],
                ['field' => 'iklan_deposit_google', 'header' => 'Iklan + Deposit Google (Semua HP)', 'type' => 'money'],
                ['field' => 'jasa_update_iklan_google', 'header' => 'Jasa update iklan google', 'type' => 'money'],
                ['field' => 'omzet', 'header' => 'Omzet', 'type' => 'money'],
            ],
            'rows' => $rows,
        ];
    }

    private function tanggalLabel(int $tglDari, int $tglSampai): string
    {
        return str_pad((string) $tglDari, 2, '0', STR_PAD_LEFT).'-'.str_pad((string) $tglSampai, 2, '0', STR_PAD_LEFT);
    }
}
