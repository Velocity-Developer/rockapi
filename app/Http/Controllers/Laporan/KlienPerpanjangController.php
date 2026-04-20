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
        return response()->json($this->buildExpiredWhmcsSummary($month));
    }

    private function buildExpiredWhmcsSummary(string $month): array
    {
        $month = $month ?: date('Y-m');

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

            return [];
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

        return [
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
        ];
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
        $detailKey = $request->input('detail_key');

        if ($monthNumber < 1 || $monthNumber > 12) {
            return response()->json(['message' => 'Bulan tidak valid'], 422);
        }

        if (! in_array($jenis, ['perpanjang', 'tidak_perpanjang', 'detail'], true)) {
            return response()->json(['message' => 'Jenis tidak valid'], 422);
        }

        if ($jenis === 'detail') {
            if (empty($detailKey)) {
                return response()->json(['message' => 'Detail key wajib diisi'], 422);
            }

            $rows = $this->getGrafikDetailRows($year, $monthNumber, $detailKey);
        } else {
            $rows = $jenis === 'perpanjang'
                ? $this->getGrafikPerpanjangRows($year, $monthNumber)
                : $this->getGrafikTidakPerpanjangRows($year, $monthNumber);
        }

        return response()->json([
            'year' => $year,
            'bulan' => $monthNumber,
            'jenis' => $jenis,
            'detail_key' => $detailKey,
            'total' => $rows->count(),
            'data' => $rows,
        ]);
    }

    private function getGrafikPerpanjangRows(int $year, int $monthNumber, bool $uniqueWebhost = true)
    {
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
                DB::raw('COALESCE(p.biaya, 0) as dibayar'),
                'wd.domain',
                'wd.status as whmcs_status',
                'wd.expirydate'
            )
            ->orderBy('ws.start_date')
            ->get()
            ->unique('subscription_id')
            ->values();

        return $uniqueWebhost ? $rows->unique('id_webhost')->values() : $rows->values();
    }

    private function getGrafikTidakPerpanjangRows(int $year, int $monthNumber, bool $uniqueWebhost = true)
    {
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
                DB::raw('NULL as cs_main_project_id'),
                DB::raw('NULL as tgl_masuk'),
                DB::raw('NULL as deskripsi'),
                DB::raw('0 as dibayar'),
                'wd.domain',
                'wd.status as whmcs_status',
                'wd.expirydate'
            )
            ->orderBy('ws.end_date')
            ->get()
            ->unique('subscription_id')
            ->values();

        return $uniqueWebhost ? $rows->unique('id_webhost')->values() : $rows->values();
    }

    private function getGrafikPerpanjangProjectRows(int $year, int $monthNumber)
    {
        return DB::table('tb_cs_main_project as p')
            ->leftJoin('tb_webhost as w', 'w.id_webhost', '=', 'p.id_webhost')
            ->leftJoin('whmcs_domains as wd', 'wd.webhost_id', '=', 'w.id_webhost')
            ->where('p.jenis', 'Perpanjangan')
            ->whereMonth('p.tgl_masuk', $monthNumber)
            ->whereYear('p.tgl_masuk', $year)
            ->select(
                'w.id_webhost',
                'w.nama_web',
                'w.tgl_mulai',
                DB::raw('NULL as subscription_id'),
                DB::raw('NULL as parent_subscription_id'),
                DB::raw('NULL as start_date'),
                DB::raw('NULL as end_date'),
                DB::raw('NULL as nextduedate'),
                DB::raw('NULL as subscription_status'),
                DB::raw('NULL as payment_status'),
                DB::raw('NULL as paid_at'),
                'p.id as cs_main_project_id',
                'p.tgl_masuk',
                'p.deskripsi',
                DB::raw('COALESCE(p.dibayar, p.biaya, 0) as dibayar'),
                'p.biaya',
                'p.tgl_masuk',
                'wd.domain',
                'wd.status as whmcs_status',
                'wd.expirydate'
            )
            ->orderBy('p.tgl_masuk')
            ->get()
            ->unique('cs_main_project_id')
            ->values();
    }

    private function summarizeGrafikPerpanjangProjectRowsByWebhost($rows)
    {
        return $rows
            ->filter(fn($row) => !empty($row->id_webhost))
            ->groupBy('id_webhost')
            ->map(function ($group) {
                $first = $group->sortBy('tgl_masuk')->first();

                return (object) [
                    'id_webhost' => $first->id_webhost,
                    'nama_web' => $first->nama_web,
                    'tgl_mulai' => $first->tgl_mulai,
                    'subscription_id' => null,
                    'parent_subscription_id' => null,
                    'start_date' => null,
                    'end_date' => null,
                    'nextduedate' => null,
                    'subscription_status' => null,
                    'payment_status' => null,
                    'paid_at' => null,
                    'cs_main_project_id' => $first->cs_main_project_id,
                    'tgl_masuk' => $first->tgl_masuk,
                    'deskripsi' => $group->pluck('deskripsi')->filter()->unique()->implode(' | '),
                    'dibayar' => (float) $group->sum(fn($row) => (float) ($row->dibayar ?? 0)),
                    'biaya' => (float) $group->sum(fn($row) => (float) ($row->biaya ?? 0)),
                    'tgl_masuk' => $first->tgl_masuk,
                    'domain' => $first->domain,
                    'whmcs_status' => $first->whmcs_status,
                    'expirydate' => $first->expirydate,
                ];
            })
            ->values();
    }

    private function getGrafikRenewalPaidOutsideSelectedMonthRows(int $year, int $monthNumber)
    {
        return $this->getGrafikPerpanjangRows($year, $monthNumber, true)
            ->filter(function ($row) use ($year, $monthNumber) {
                if (empty($row->paid_at)) {
                    return false;
                }

                $paidAt = Carbon::parse($row->paid_at);

                return (int) $paidAt->month !== $monthNumber || (int) $paidAt->year !== $year;
            })
            ->values();
    }

    private function mapSubscriptionRowsToProjectLikeRows($rows)
    {
        return $rows->map(function ($row) {
            return (object) [
                'id_webhost' => $row->id_webhost,
                'nama_web' => $row->nama_web,
                'tgl_mulai' => $row->tgl_mulai,
                'subscription_id' => $row->subscription_id,
                'parent_subscription_id' => $row->parent_subscription_id,
                'start_date' => $row->start_date,
                'end_date' => $row->end_date,
                'nextduedate' => $row->nextduedate,
                'subscription_status' => $row->subscription_status,
                'payment_status' => $row->payment_status,
                'paid_at' => $row->paid_at,
                'cs_main_project_id' => $row->cs_main_project_id,
                'tgl_masuk' => $row->paid_at ?: $row->tgl_masuk,
                'deskripsi' => $row->deskripsi,
                'dibayar' => (float) ($row->dibayar ?? 0),
                'biaya' => (float) ($row->dibayar ?? 0),
                'created_at' => $row->paid_at,
                'domain' => $row->domain,
                'whmcs_status' => $row->whmcs_status,
                'expirydate' => $row->expirydate,
            ];
        })->values();
    }

    private function getExpiredWhmcsRowsByMonth(int $year, int $monthNumber)
    {
        $today = now()->startOfDay();

        $whmcsUsers = WhmcsUser::with([
            'hostings' => function ($q) use ($monthNumber, $year) {
                $q->whereMonth('nextduedate', $monthNumber)
                    ->whereYear('nextduedate', $year)
                    ->whereHas('webhost')
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
                                ]);
                        }
                    ]);
            },
            'domains' => function ($q) use ($monthNumber, $year) {
                $q->whereMonth('expirydate', $monthNumber)
                    ->whereYear('expirydate', $year)
                    ->whereHas('webhost')
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
                                ]);
                        }
                    ]);
            }
        ])
            ->where(function ($q) use ($monthNumber, $year) {
                $q->whereHas('hostings', function ($q2) use ($monthNumber, $year) {
                    $q2->whereMonth('nextduedate', $monthNumber)
                        ->whereYear('nextduedate', $year)
                        ->whereHas('webhost');
                })
                    ->orWhereHas('domains', function ($q2) use ($monthNumber, $year) {
                        $q2->whereMonth('expirydate', $monthNumber)
                            ->whereYear('expirydate', $year)
                            ->whereHas('webhost');
                    });
            })
            ->get();

        $data = [];

        foreach ($whmcsUsers as $user) {
            if ($user->email === 'bantuanvelocity@gmail.com') {
                continue;
            }

            foreach ($user->domains as $domain) {
                $webhost = $domain->webhost ?? null;
                $domainName = strtolower($domain->domain);

                $data[$domainName]['status'] = false;
                $data[$domainName]['domain'] = $domain;
                $data[$domainName]['domain_name'] = $domain->domain;
                $data[$domainName]['user'] = [
                    'id' => $user->id,
                    'whmcs_id' => $user->whmcs_id,
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                ];
                $data[$domainName]['webhost'] = $webhost;
                $data[$domainName]['webhost_available'] = $webhost ? 1 : 0;
                $data[$domainName]['project'] = $webhost?->csMainProjects[0] ?? null;
                $data[$domainName]['project_available'] = $data[$domainName]['project'] ? 1 : 0;

                if ($domain->expirydate) {
                    $data[$domainName]['expiry'] = $domain->expirydate;
                    $data[$domainName]['status'] = Carbon::parse($domain->expirydate)->startOfDay()->gte($today);
                }
            }

            foreach ($user->hostings as $hosting) {
                $webhost = $hosting->webhost ?? null;
                $domainName = strtolower($hosting->domain);

                $data[$domainName]['hosting'] = $hosting;
                $data[$domainName]['domain_name'] = $hosting->domain;
                $data[$domainName]['user'] = [
                    'id' => $user->id,
                    'whmcs_id' => $user->whmcs_id,
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                ];

                if (empty($data[$domainName]['webhost'])) {
                    $data[$domainName]['status'] = $data[$domainName]['status'] ?? false;
                    $data[$domainName]['webhost'] = $webhost;
                    $data[$domainName]['webhost_available'] = $webhost ? 1 : 0;
                }

                if (empty($data[$domainName]['project'])) {
                    $data[$domainName]['project'] = $webhost?->csMainProjects[0] ?? null;
                    $data[$domainName]['project_available'] = $data[$domainName]['project'] ? 1 : 0;
                }

                if (empty($data[$domainName]['expiry'])) {
                    $data[$domainName]['expiry'] = $hosting->nextduedate;
                }

                if ($hosting->nextduedate && Carbon::parse($hosting->nextduedate)->startOfDay()->gte($today)) {
                    $data[$domainName]['status'] = true;
                }
            }
        }

        return collect(array_values($data));
    }

    private function getGrafikDetailRows(int $year, int $monthNumber, string $detailKey)
    {
        $perpanjangRows = $this->getGrafikPerpanjangRows($year, $monthNumber, false);
        $perpanjangUniqueRows = $perpanjangRows->unique('id_webhost')->values();
        $tidakPerpanjangRows = $this->getGrafikTidakPerpanjangRows($year, $monthNumber, false);
        $tidakPerpanjangUniqueRows = $tidakPerpanjangRows->unique('id_webhost')->values();
        $perpanjangProjectRows = $this->getGrafikPerpanjangProjectRows($year, $monthNumber);
        $perpanjangProjectRowsByWebhost = $this->summarizeGrafikPerpanjangProjectRowsByWebhost($perpanjangProjectRows);
        $renewalPaidOutsideSelectedMonthRows = $this->mapSubscriptionRowsToProjectLikeRows(
            $this->getGrafikRenewalPaidOutsideSelectedMonthRows($year, $monthNumber)
        );

        return match ($detailKey) {
            'total_webhost', 'ratio_perpanjang_webhost' => $perpanjangUniqueRows
                ->concat($tidakPerpanjangUniqueRows)
                ->unique('id_webhost')
                ->values(),
            'webhost_perpanjang' => $perpanjangUniqueRows,
            'webhost_tidak_perpanjang' => $tidakPerpanjangUniqueRows,
            'total_pemasukkan_perpanjang' => $perpanjangProjectRows->values(),
            'total_data_masuk_perpanjang', 'rata_rata_biaya_perpanjang' => $perpanjangProjectRowsByWebhost->values(),
            'ppj_masuk_dari_bulan_ini' => $perpanjangProjectRowsByWebhost
                ->filter(function ($row) use ($monthNumber, $year) {
                    if (empty($row->tgl_mulai)) {
                        return false;
                    }

                    $tanggalMulai = Carbon::parse($row->tgl_mulai);
                    return (int) $tanggalMulai->month === $monthNumber;
                })
                ->values(),
            'ppj_masuk_dari_bulan_lain' => $perpanjangProjectRowsByWebhost
                ->filter(function ($row) use ($monthNumber) {
                    if (empty($row->tgl_mulai)) {
                        return false;
                    }

                    $tanggalMulai = Carbon::parse($row->tgl_mulai);
                    return (int) $tanggalMulai->month !== $monthNumber;
                })
                ->values(),
            'ppj_masuk_bulan_ini_terbayar_bulan_lalu' => $renewalPaidOutsideSelectedMonthRows->values(),
            'perpanjang_masuk_termahal' => $perpanjangProjectRows
                ->filter(function ($row) use ($perpanjangProjectRows) {
                    $maxBiaya = (float) $perpanjangProjectRows->max('biaya');
                    return (float) $row->biaya === $maxBiaya && $maxBiaya > 0;
                })
                ->values(),
            default => response()->json(['message' => 'Detail key tidak valid'], 422)->throwResponse(),
        };
    }

    private function buildGrafikMonthData(int $year, int $monthNumber): array
    {
        $perpanjangProjectRows = $this->getGrafikPerpanjangProjectRows($year, $monthNumber);
        $perpanjangProjectRowsByWebhost = $this->summarizeGrafikPerpanjangProjectRowsByWebhost($perpanjangProjectRows);
        $renewalPaidOutsideSelectedMonthRows = $this->getGrafikRenewalPaidOutsideSelectedMonthRows($year, $monthNumber);
        $expiredWhmcsSummary = $this->buildExpiredWhmcsSummary(sprintf('%04d-%02d', $year, $monthNumber));
        $totalPemasukkanPerpanjang = (float) $perpanjangProjectRows->sum('biaya');
        $totalDataPerpanjang = $perpanjangProjectRowsByWebhost->count();
        $perpanjangTermahal = (float) $perpanjangProjectRows->max('biaya');

        $perpanjang = (int) ($expiredWhmcsSummary['total_perpanjang'] ?? 0);
        $tidak_perpanjang = (int) ($expiredWhmcsSummary['total_tidak_perpanjang'] ?? 0);
        $total = $perpanjang + $tidak_perpanjang;
        $ratio = $total > 0 ? round(($perpanjang / $total) * 100, 1) : 0;

        $ppjDariBulanIni = $perpanjangProjectRowsByWebhost
            ->filter(function ($row) use ($monthNumber) {
                if (empty($row->tgl_mulai)) {
                    return false;
                }

                $tanggalMulai = Carbon::parse($row->tgl_mulai);
                return (int) $tanggalMulai->month === $monthNumber;
            })
            ->count();

        $ppjDariBulanLain = $perpanjangProjectRowsByWebhost
            ->filter(function ($row) use ($monthNumber) {
                if (empty($row->tgl_mulai)) {
                    return false;
                }

                $tanggalMulai = Carbon::parse($row->tgl_mulai);
                return (int) $tanggalMulai->month !== $monthNumber;
            })
            ->count();

        $ppjBulanIniTerbayarBulanLalu = $renewalPaidOutsideSelectedMonthRows->count();

        return [
            'month' => Carbon::create($year, $monthNumber, 1)->translatedFormat('F'),
            'month_number' => $monthNumber,
            'year' => $year,
            'perpanjang' => $perpanjang,
            'tidak_perpanjang' => $tidak_perpanjang,
            'total' => $total,
            'ratio' => $ratio,
            'detail' => [
                [
                    'key' => 'total_webhost',
                    'label' => 'Total Webhost',
                    'value' => $total,
                ],
                [
                    'key' => 'webhost_perpanjang',
                    'label' => 'Webhost Perpanjang',
                    'value' => $perpanjang,
                ],
                [
                    'key' => 'webhost_tidak_perpanjang',
                    'label' => 'Webhost Tidak Perpanjang',
                    'value' => $tidak_perpanjang,
                ],
                [
                    'key' => 'ratio_perpanjang_webhost',
                    'label' => 'Ratio Perpanjang',
                    'value' => $ratio,
                ],
            ],
            'detail_perpanjang' => [
                [
                    'key' => 'total_pemasukkan_perpanjang',
                    'label' => 'Total Pemasukkan Perpanjang',
                    'value' => $totalPemasukkanPerpanjang,
                ],
                [
                    'key' => 'total_data_masuk_perpanjang',
                    'label' => 'Total Data Perpanjang',
                    'value' => $totalDataPerpanjang,
                ],
                [
                    'key' => 'ppj_masuk_dari_bulan_ini',
                    'label' => 'PPJ dari bulan ini',
                    'value' => $ppjDariBulanIni,
                ],
                [
                    'key' => 'ppj_masuk_dari_bulan_lain',
                    'label' => 'PPJ dari bulan lain',
                    'value' => $ppjDariBulanLain,
                ],
                [
                    'key' => 'ppj_masuk_bulan_ini_terbayar_bulan_lalu',
                    'label' => 'PPJ bulan ini yang terbayar di bulan lain',
                    'value' => $ppjBulanIniTerbayarBulanLalu,
                ],
                [
                    'key' => 'rata_rata_biaya_perpanjang',
                    'label' => 'Rata-rata biaya perpanjang',
                    'value' => $totalDataPerpanjang > 0 ? round($totalPemasukkanPerpanjang / $totalDataPerpanjang, 2) : 0,
                ],
                [
                    'key' => 'perpanjang_masuk_termahal',
                    'label' => 'Perpanjang termahal',
                    'value' => $perpanjangTermahal,
                ],
            ]
        ];
    }
}
