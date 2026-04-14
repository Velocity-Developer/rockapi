<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Webhost;
use App\Models\WhmcsUser;
use App\Models\WhmcsHosting;
use App\Models\WhmcsDomain;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\WHMCSSyncServices;

class KlienPerpanjangController extends Controller
{
    private $jenis_pembuatan = [
        'Pembuatan',
        'Pembuatan apk',
        'Pembuatan apk custom',
        'Pembuatan Tanpa Domain',
        'Pembuatan Tanpa Hosting',
        'Pembuatan Tanpa Domain+Hosting',
    ];

    public function index(Request $request)
    {
        $results = [];
        $date = $request->input('bulan');
        $date = Carbon::parse($date);
        $bulan = $date->format('m');
        $tahun = $date->format('Y');

        // date 1 tahun lalu
        $date_y = $request->input('bulan');
        $date_y = Carbon::parse($date_y);
        $date_1_tahun_lalu = $date_y->subYear();
        $bulan_lalu = $date_1_tahun_lalu->format('m');
        $tahun_lalu = $date_1_tahun_lalu->format('Y');

        $results['date'] = [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'bulan_lalu' => $bulan_lalu,
            'tahun_lalu' => $tahun_lalu,
        ];

        $jenis_pembuatan_perpanjang = array_merge($this->jenis_pembuatan, ['Perpanjangan']);

        /**
         * Perpanjang Bulan Ini
         * mengambil data webhost yang memiliki cs_main_project dengan jenis = 'Perpanjangan' dengan tgl_masuk di bulan ini
         */
        $perpanjang_bulan_ini = Webhost::with([
            'csMainProjects' => function ($q) use ($bulan, $tahun) {
                $q->where(function ($query) use ($bulan, $tahun) {
                    // Perpanjangan di bulan & tahun ini
                    $query->where('jenis', 'Perpanjangan')
                        ->whereMonth('tgl_masuk', $bulan)
                        ->whereYear('tgl_masuk', $tahun);
                })
                    ->orWhere(function ($query) {
                        // Semua Pembuatan tanpa filter waktu
                        $query->whereIn('jenis', $this->jenis_pembuatan);
                    })
                    ->orderBy('tgl_masuk', 'asc'); // opsional, biar urut
            },
        ])
            ->whereHas('csMainProjects', function ($query) use ($bulan, $tahun) {
                $query->where('jenis', 'Perpanjangan')
                    ->whereMonth('tgl_masuk', $bulan)
                    ->whereYear('tgl_masuk', $tahun);
            })
            ->get();

        $perpanjang_bulan_ini_total = $perpanjang_bulan_ini->count();
        $results['meta']['perpanjang_bulan_ini'] = $perpanjang_bulan_ini;
        $perpanjang_bulan_ini_nominal = $perpanjang_bulan_ini
            ->flatMap->csMainProjects // gabungkan semua csMainProjects jadi satu collection
            ->where('jenis', 'Perpanjangan')
            ->sum('biaya');

        /**
         * Perpanjang Baru
         * mengambil data webhost yang memiliki cs_main_project dengan jenis = 'Perpanjangan' dengan tgl_masuk di bulan ini
         * dan tgl_masuk di tahun ini
         * dan memiliki cs_main_project dengan jenis = $jenis_pembuatan dengan tgl_masuk di bulan tahun lalu
         * dan tgl_masuk di tahun lalu
         */
        $perpanjang_baru = Webhost::with([
            'csMainProjects' => function ($q) use ($bulan, $tahun) {
                $q->where(function ($query) use ($bulan, $tahun) {
                    // Perpanjangan di bulan & tahun ini
                    $query->where('jenis', 'Perpanjangan')
                        ->whereMonth('tgl_masuk', $bulan)
                        ->whereYear('tgl_masuk', $tahun);
                })
                    ->orWhere(function ($query) {
                        // Semua Pembuatan tanpa filter waktu
                        $query->whereIn('jenis', $this->jenis_pembuatan);
                    })
                    ->orderBy('tgl_masuk', 'asc'); // opsional, biar urut
            },
        ])
            // Filter parent agar hanya yang memenuhi dua kondisi
            ->whereHas('csMainProjects', function ($query) use ($bulan, $tahun) {
                $query->where('jenis', 'Perpanjangan')
                    ->whereMonth('tgl_masuk', $bulan)
                    ->whereYear('tgl_masuk', $tahun);
            })
            ->whereHas('csMainProjects', function ($query) use ($tahun_lalu) {
                $query->whereIn('jenis', $this->jenis_pembuatan)
                    ->whereYear('tgl_masuk', $tahun_lalu);
            })
            ->get();

        $perpanjang_baru_total = $perpanjang_baru->count();
        $results['meta']['perpanjang_baru'] = $perpanjang_baru;
        $perpanjang_baru_nominal = $perpanjang_baru
            ->flatMap->csMainProjects
            ->where('jenis', 'Perpanjangan') // hanya ambil yang jenis perpanjangan
            ->filter(function ($item) use ($tahun, $bulan) { // filter perpanjangan bulan ini
                return Carbon::parse($item->tgl_masuk)->year == $tahun
                    && Carbon::parse($item->tgl_masuk)->month == $bulan;
            })
            ->sum('biaya');

        /**
         * Tidak Perpanjang
         * mengambil data webhost yang memiliki cs_main_project dengan jenis = 'Perpanjangan' dengan tgl_masuk di tahun lalu
         * dan memiliki cs_main_project dengan jenis = $jenis_pembuatan dengan tgl_masuk di tahun lalu
         * dan tidak memiliki cs_main_project dengan jenis = 'Perpanjangan' dengan tgl_masuk di bulan tahun ini
         */
        $bulan_sebelum = Carbon::createFromDate($tahun, $bulan, 1)->subMonth();
        $bulan_setelah = Carbon::createFromDate($tahun, $bulan, 1)->addMonth();

        $tidak_perpanjang = Webhost::with([
            'csMainProjects' => function ($query) use ($jenis_pembuatan_perpanjang) {
                $query->whereIn('jenis', $jenis_pembuatan_perpanjang);
            },
        ])
            // Filter parent agar hanya yang memenuhi dua kondisi
            // Harus ada perpanjangan di bulan lalu
            ->whereHas('csMainProjects', function ($query) use ($bulan_lalu, $tahun_lalu) {
                $query->where('jenis', 'Perpanjangan')
                    ->whereMonth('tgl_masuk', $bulan_lalu)
                    ->whereYear('tgl_masuk', $tahun_lalu);
            })
            // Tidak ada perpanjangan di tahun berjalan
            ->whereDoesntHave('csMainProjects', function ($query) use ($tahun) {
                $query->where('jenis', 'Perpanjangan')
                    // ->whereMonth('tgl_masuk', $bulan)
                    ->whereYear('tgl_masuk', $tahun);
            })
            // Tidak ada perpanjangan di bulan sebelum
            ->whereDoesntHave('csMainProjects', function ($query) use ($bulan_sebelum) {
                $query->where('jenis', 'Perpanjangan')
                    ->whereMonth('tgl_masuk', $bulan_sebelum->month)
                    ->whereYear('tgl_masuk', $bulan_sebelum->year);
            })
            // Tidak ada perpanjangan di bulan setelah
            ->whereDoesntHave('csMainProjects', function ($query) use ($bulan_setelah) {
                $query->where('jenis', 'Perpanjangan')
                    ->whereMonth('tgl_masuk', $bulan_setelah->month)
                    ->whereYear('tgl_masuk', $bulan_setelah->year);
            })
            ->get();

        $tidak_perpanjang_total = $tidak_perpanjang->count();
        $results['meta']['tidak_perpanjang'] = $tidak_perpanjang;
        $tidak_perpanjang_nominal = $tidak_perpanjang
            ->flatMap->csMainProjects
            ->where('jenis', 'Perpanjangan')
            ->filter(function ($item) use ($tahun_lalu, $bulan_lalu) { // filter perpanjangan bulan ini
                return \Carbon\Carbon::parse($item->tgl_masuk)->year == $tahun_lalu
                    && \Carbon\Carbon::parse($item->tgl_masuk)->month == $bulan_lalu;
            })
            ->sum('biaya');

        $results['data'] = [
            'perpanjang' => [
                'label' => 'Perpanjang',
                'total' => $perpanjang_bulan_ini_total,
                'nominal' => $perpanjang_bulan_ini_nominal,
                'webhosts' => $perpanjang_bulan_ini,
            ],
            'perpanjang_baru' => [
                'label' => 'Perpanjang Baru',
                'total' => $perpanjang_baru_total,
                'nominal' => $perpanjang_baru_nominal,
                'webhosts' => $perpanjang_baru,
            ],
            'tidak_perpanjang' => [
                'label' => 'Tidak Perpanjang',
                'total' => $tidak_perpanjang_total,
                'nominal' => $tidak_perpanjang_nominal,
                'webhosts' => $tidak_perpanjang,
            ],
        ];

        return response()->json($results);
    }

    public function expiredWhmcs(Request $request)
    {

        $month = $request->input('month', date('Y-m'));

        $m = date('m', strtotime($month));
        $y = date('Y', strtotime($month));

        $years = [$y, $y + 1];

        $query = WhmcsUser::with([
            'hostings' => function ($q) use ($m, $years) {
                $q->whereMonth('nextduedate', $m)
                    ->whereIn(DB::raw('YEAR(nextduedate)'), $years)
                    // ->whereHas('webhost') // WAJIB ADA WEBHOST
                    ->with([
                        'webhost' => function ($q2) {
                            $q2->select('id_webhost', 'nama_web', 'tgl_mulai')
                                ->with([
                                    'csMainProjects' => function ($q3) {
                                        $q3->select('id', 'id_webhost', 'jenis', 'tgl_masuk', 'deskripsi', 'biaya')
                                            ->where('jenis', 'Perpanjangan')
                                            ->orderByDesc('tgl_masuk')
                                            ->limit(1);
                                    },
                                    'pembuatan' => function ($q3) {
                                        $q3->select(
                                            'id',
                                            'id_webhost',
                                            'jenis',
                                            'tgl_masuk',
                                            'deskripsi',
                                            'biaya'
                                        );
                                    }
                                ]);
                        }
                    ]);
            },
            'domains' => function ($q) use ($m, $years) {
                $q->whereMonth('expirydate', $m)
                    ->whereIn(DB::raw('YEAR(expirydate)'), $years)
                    // ->whereHas('webhost') // WAJIB ADA WEBHOST
                    ->with([
                        'webhost' => function ($q2) {
                            $q2->select('id_webhost', 'nama_web', 'tgl_mulai')
                                ->with([
                                    'csMainProjects' => function ($q3) {
                                        $q3->select('id', 'id_webhost', 'jenis', 'tgl_masuk', 'deskripsi', 'biaya')
                                            ->where('jenis', 'Perpanjangan')
                                            ->orderByDesc('tgl_masuk')
                                            ->limit(1);
                                    },
                                    'pembuatan' => function ($q3) {
                                        $q3->select(
                                            'id',
                                            'id_webhost',
                                            'jenis',
                                            'tgl_masuk',
                                            'deskripsi',
                                            'biaya'
                                        );
                                    }
                                ]);
                        }
                    ]);
            }
        ]);

        $query->where(function ($q) use ($m, $years) {

            $q->whereHas('hostings', function ($q2) use ($m, $years) {
                $q2->whereMonth('nextduedate', $m)
                    ->whereIn(DB::raw('YEAR(nextduedate)'), $years);
            })

                ->orWhereHas('domains', function ($q2) use ($m, $years) {
                    $q2->whereMonth('expirydate', $m)
                        ->whereIn(DB::raw('YEAR(expirydate)'), $years);
                });
        });

        $whmcsUsers = $query->get();

        $data = [];

        if ($whmcsUsers->isEmpty()) {

            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->format('Y-m-d');
            $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->format('Y-m-d');

            // mengambil data domain yang sudah expired dari WHMCS
            (new WHMCSSyncServices())->syncDomainExpired($start, $end);

            // mengambil data hosting expired dari WHMCS
            (new WHMCSSyncServices())->syncHostingExpired($start, $end);

            return response()->json([]);
        }

        foreach ($whmcsUsers as $user) {

            //jika nama email = 'bantuanvelocity@gmail.com', skip
            if ($user->email == 'bantuanvelocity@gmail.com') {
                continue;
            }

            //domains
            $domain_name = '';
            foreach ($user->domains as $domain) {

                $webhost = $domain->webhost ?? null;
                $webhostTahun = $webhost ? date("Y", strtotime($webhost->tgl_mulai)) : null;
                //jika webhost.tgl_mulai 
                if ($webhostTahun && $webhostTahun == $y) {
                    continue;
                }

                $domain_name = strtolower($domain->domain);
                $data[$domain_name]['status'] = false;
                $data[$domain_name]['domain'] = $domain;
                $data[$domain_name]['domain_name'] = $domain->domain;
                $data[$domain_name]['user'] = [
                    'id' => $user->id,
                    'whmcs_id' => $user->whmcs_id,
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                ];
                $data[$domain_name]['webhost'] = $domain->webhost;
                $data[$domain_name]['webhost_available'] = $domain->webhost ? 1 : 0;
                $data[$domain_name]['project'] = $domain->webhost->csMainProjects[0] ?? null;
                $data[$domain_name]['project_available'] = $data[$domain_name]['project'] ? 1 : 0;
                if ($domain->expirydate) {
                    $data[$domain_name]['expiry']  = $domain->expirydate;
                    $expirytahun = date("Y", strtotime($domain->expirydate));
                    $data[$domain_name]['status']  = $expirytahun > $y && isset($domain->webhost->csMainProjects) ? true : false;
                }
            }
            //hostings
            foreach ($user->hostings as $hosting) {

                $webhost = $hosting->webhost ?? null;
                $webhostTahun = $webhost ? date("Y", strtotime($webhost->tgl_mulai)) : null;
                //jika webhost.tgl_mulai 
                if ($webhostTahun && $webhostTahun == $y) {
                    continue;
                }

                $domain_name = strtolower($hosting->domain);
                $data[$domain_name]['hosting'] = $hosting;
                $data[$domain_name]['domain_name'] = $hosting->domain;
                $data[$domain_name]['user'] = [
                    'id' => $user->id,
                    'whmcs_id' => $user->whmcs_id,
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                ];
                if (!isset($data[$domain_name]['webhost']) || isset($data[$domain_name]['webhost']) && empty($data[$domain_name]['webhost'])) {
                    $data[$domain_name]['status'] = isset($data[$domain_name]['status']) ? $data[$domain_name]['status'] : false;
                    $data[$domain_name]['webhost'] = $hosting->webhost;
                    $data[$domain_name]['webhost_available'] = $hosting->webhost ? 1 : 0;
                }
                if (!isset($data[$domain_name]['project']) || isset($data[$domain_name]['project']) && empty($data[$domain_name]['project'])) {
                    $data[$domain_name]['project'] = $hosting->webhost->csMainProjects[0] ?? null;
                    $data[$domain_name]['project_available'] = $data[$domain_name]['project'] ? 1 : 0;
                }
                if (!isset($data[$domain_name]['expiry']) || isset($data[$domain_name]['expiry']) && empty($data[$domain_name]['expiry'])) {
                    $data[$domain_name]['expiry']  = $hosting->nextduedate;
                    $expirytahun = date("Y", strtotime($hosting->nextduedate));
                    $data[$domain_name]['status']  = $expirytahun > $y && isset($hosting->webhost->csMainProjects) ? true : false;
                }
            }
        }

        $reindexed_array = array_values($data);
        $total = count($reindexed_array);
        $total_perpanjang = collect($reindexed_array)->filter(function ($item) {
            return $item['status'] === true;
        })->count();

        return response()->json([
            'total' => $total,
            'total_domain' => $whmcsUsers->sum(function ($item) {
                return $item->domains->count();
            }),
            'total_hosting' => $whmcsUsers->sum(function ($item) {
                return $item->hostings->count();
            }),
            'total_perpanjang' => $total_perpanjang,
            'total_tidak_perpanjang' => ($total - $total_perpanjang),
            'data' => $reindexed_array
        ]);
    }

    public function grafik(Request $request)
    {
        $year = (int) $request->input('tahun', now()->year);
        $currentYear = now()->year;

        // tentukan batas bulan
        $maxMonth = ($year === $currentYear) ? now()->month : 12;

        $months = collect(range(1, $maxMonth))
            ->map(function ($month) use ($year) {
                return [
                    'month' => $month,
                    'name'  => Carbon::create($year, $month, 1)->translatedFormat('F'),
                    'year'  => $year,
                ];
            });

        if ($request->filled('bulan')) {
            $monthNumber = (int) $request->input('bulan');
            if ($monthNumber < 1 || $monthNumber > 12) {
                return response()->json(['message' => 'Bulan tidak valid'], 422);
            }

            return response()->json($this->buildGrafikMonthData($year, $monthNumber));
        }

        $data = [];
        foreach ($months as $month) {
            $data[] = $this->buildGrafikMonthData($year, (int) $month['month']);
        }

        return response()->json([
            'year' => $year,
            'months' => $months,
            'data' => $data,
        ]);
    }

    public function grafikData(Request $request)
    {
        $year = (int) $request->input('tahun', now()->year);
        $monthNumber = (int) $request->input('bulan');
        $jenis = $request->input('jenis');

        if ($monthNumber < 1 || $monthNumber > 12) {
            return response()->json(['message' => 'Bulan tidak valid'], 422);
        }

        if (! in_array($jenis, ['perpanjang', 'tidak_perpanjang'], true)) {
            return response()->json(['message' => 'Jenis tidak valid'], 422);
        }

        if ($jenis === 'perpanjang') {
            $rows = DB::table('webhost_subscriptions as ws')
                ->join('tb_webhost as w', 'w.id_webhost', '=', 'ws.webhost_id')
                ->leftJoin('whmcs_domains as wd', 'wd.webhost_id', '=', 'w.id_webhost')
                ->leftJoin('tb_cs_main_project as p', 'p.id', '=', 'ws.cs_main_project_id')
                ->whereNotNull('ws.parent_subscription_id')
                ->whereMonth('ws.start_date', $monthNumber)
                ->whereYear('ws.start_date', $year)
                ->select(
                    'w.id_webhost',
                    'w.nama_web',
                    'w.tgl_mulai',
                    'ws.id as subscription_id',
                    'ws.parent_subscription_id',
                    'ws.start_date',
                    'ws.end_date',
                    'ws.nextduedate',
                    'ws.status as subscription_status',
                    'ws.payment_status',
                    'ws.paid_at',
                    'p.id as cs_main_project_id',
                    'p.tgl_masuk',
                    'p.deskripsi',
                    'p.biaya',
                    'wd.domain',
                    'wd.status as whmcs_status',
                    'wd.expirydate'
                )
                ->orderBy('ws.start_date')
                ->get()
                ->unique('id_webhost')
                ->values();
        } else {
            $rows = DB::table('webhost_subscriptions as ws')
                ->join('tb_webhost as w', 'w.id_webhost', '=', 'ws.webhost_id')
                ->join('whmcs_domains as wd', 'wd.webhost_id', '=', 'w.id_webhost')
                ->whereMonth('ws.end_date', $monthNumber)
                ->whereYear('ws.end_date', $year)
                ->where('ws.status', 'Expired')
                ->whereNotExists(function ($query) use ($monthNumber, $year) {
                    $query->select(DB::raw(1))
                        ->from('webhost_subscriptions as renewal')
                        ->whereColumn('renewal.webhost_id', 'ws.webhost_id')
                        ->whereNotNull('renewal.parent_subscription_id')
                        ->whereMonth('renewal.start_date', $monthNumber)
                        ->whereYear('renewal.start_date', $year);
                })
                ->select(
                    'w.id_webhost',
                    'w.nama_web',
                    'w.tgl_mulai',
                    'ws.id as subscription_id',
                    'ws.parent_subscription_id',
                    'ws.start_date',
                    'ws.end_date',
                    'ws.nextduedate',
                    'ws.status as subscription_status',
                    'ws.payment_status',
                    'ws.paid_at',
                    'wd.domain',
                    'wd.status as whmcs_status',
                    'wd.expirydate'
                )
                ->orderBy('ws.end_date')
                ->get()
                ->unique('id_webhost')
                ->values();
        }

        return response()->json([
            'year' => $year,
            'bulan' => $monthNumber,
            'jenis' => $jenis,
            'total' => $rows->count(),
            'data' => $rows,
        ]);
    }

    private function buildGrafikMonthData(int $year, int $monthNumber): array
    {
        $monthStart = Carbon::create($year, $monthNumber, 1)->startOfMonth();
        $previousMonthStart = $monthStart->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $monthStart->copy()->subMonth()->endOfMonth();
        $totalPemasukkanPerpanjang = (float) DB::table('tb_cs_main_project')
            ->where('jenis', 'Perpanjangan')
            ->whereMonth('tgl_masuk', $monthNumber)
            ->whereYear('tgl_masuk', $year)
            ->sum('biaya');
        $totalDataPerpanjang = DB::table('tb_cs_main_project')
            ->where('jenis', 'Perpanjangan')
            ->whereMonth('tgl_masuk', $monthNumber)
            ->whereYear('tgl_masuk', $year)
            ->count();
        $perpanjangTermahal = (float) DB::table('tb_cs_main_project')
            ->where('jenis', 'Perpanjangan')
            ->whereMonth('tgl_masuk', $monthNumber)
            ->whereYear('tgl_masuk', $year)
            ->max('biaya');

        $perpanjangRows = DB::table('webhost_subscriptions as ws')
            ->leftJoin('tb_cs_main_project as p', 'p.id', '=', 'ws.cs_main_project_id')
            ->whereNotNull('ws.parent_subscription_id')
            ->whereMonth('ws.start_date', $monthNumber)
            ->whereYear('ws.start_date', $year)
            ->select(
                'ws.id',
                'ws.webhost_id',
                'ws.parent_subscription_id',
                'ws.start_date',
                'ws.end_date',
                'ws.nextduedate',
                'ws.paid_at',
                'ws.cs_main_project_id',
                'p.biaya'
            )
            ->get();

        $tidakPerpanjangRows = DB::table('webhost_subscriptions as ws')
            ->join('whmcs_domains as wd', 'wd.webhost_id', '=', 'ws.webhost_id')
            ->whereMonth('ws.end_date', $monthNumber)
            ->whereYear('ws.end_date', $year)
            ->where('ws.status', 'Expired')
            ->whereNotExists(function ($query) use ($monthNumber, $year) {
                $query->select(DB::raw(1))
                    ->from('webhost_subscriptions as renewal')
                    ->whereColumn('renewal.webhost_id', 'ws.webhost_id')
                    ->whereNotNull('renewal.parent_subscription_id')
                    ->whereMonth('renewal.start_date', $monthNumber)
                    ->whereYear('renewal.start_date', $year);
            })
            ->select('ws.id', 'ws.webhost_id', 'ws.parent_subscription_id', 'ws.start_date', 'ws.end_date', 'ws.nextduedate', 'ws.paid_at')
            ->get();

        $perpanjangIds = $perpanjangRows->pluck('webhost_id')->unique()->values();
        $tidakPerpanjangIds = $tidakPerpanjangRows->pluck('webhost_id')->unique()->values();

        $perpanjang = $perpanjangIds->count();
        $tidak_perpanjang = $tidakPerpanjangIds->count();
        $total = $perpanjang + $tidak_perpanjang;
        $ratio = $total > 0 ? round(($perpanjang / $total) * 100, 1) : 0;

        $paymentEntries = $perpanjangRows
            ->filter(fn($row) => ! empty($row->paid_at))
            ->map(function ($row) {
                return [
                    'webhost_id' => $row->webhost_id,
                    'payment_date' => Carbon::parse($row->paid_at),
                    'amount' => (float) ($row->biaya ?? 0),
                ];
            })
            ->values();

        $ppjDariBulanIni = $paymentEntries
            ->filter(fn($entry) => (int) $entry['payment_date']->month === $monthNumber && (int) $entry['payment_date']->year === $year)
            ->pluck('webhost_id')
            ->unique()
            ->count();

        $ppjBulanIniTerbayarBulanLalu = $paymentEntries
            ->filter(fn($entry) => $entry['payment_date']->between($previousMonthStart, $previousMonthEnd))
            ->pluck('webhost_id')
            ->unique()
            ->count();

        $ppjDariBulanLain = $paymentEntries
            ->filter(function ($entry) use ($monthNumber, $year, $previousMonthStart, $previousMonthEnd) {
                $isPaidThisMonth = (int) $entry['payment_date']->month === $monthNumber
                    && (int) $entry['payment_date']->year === $year;

                $isPaidPreviousMonth = $entry['payment_date']->between($previousMonthStart, $previousMonthEnd);

                return ! $isPaidThisMonth && ! $isPaidPreviousMonth;
            })
            ->pluck('webhost_id')
            ->unique()
            ->count();

        return [
            'month' => Carbon::create($year, $monthNumber, 1)->translatedFormat('F'),
            'month_number' => $monthNumber,
            'year' => $year,
            'perpanjang' => $perpanjang,
            'tidak_perpanjang' => $tidak_perpanjang,
            'total' => $total,
            'ratio' => $ratio,
            'rincian' => [
                'Total Webhost' => $total,
                'Webhost Perpanjang' => $perpanjang,
                'Webhost Tidak Perpanjang' => $tidak_perpanjang,
                'Ratio Perpanjang (%)' => $ratio,
            ],
            'rincian_bulan' => [
                'Total Pemasukkan Perpanjang' => $totalPemasukkanPerpanjang,
                'PPJ dari bulan ini' => $ppjDariBulanIni,
                'PPJ dari bulan lain' => $ppjDariBulanLain,
                'PPJ bulan ini yang terbayar di bulan lalu' => $ppjBulanIniTerbayarBulanLalu,
                'Rata-rata biaya perpanjang' => $totalDataPerpanjang > 0 ? round($totalPemasukkanPerpanjang / $totalDataPerpanjang, 2) : 0,
                'Perpanjang termahal' => $perpanjangTermahal,
            ],
        ];
    }
}
