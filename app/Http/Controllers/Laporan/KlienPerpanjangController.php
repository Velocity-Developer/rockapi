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
            ->sum('dibayar');

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
            ->sum('dibayar');

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
            ->sum('dibayar');

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
                                        $q3->select('id', 'id_webhost', 'jenis', 'tgl_masuk', 'deskripsi', 'dibayar')
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
                                            'dibayar'
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
                                        $q3->select('id', 'id_webhost', 'jenis', 'tgl_masuk', 'deskripsi', 'dibayar')
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
                                            'dibayar'
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
        $yearStart = Carbon::create($year, 1, 1)->startOfYear();
        $yearEnd = Carbon::create($year, 12, 31)->endOfYear();
        $yearEndNext = Carbon::create($year + 1, 12, 31)->endOfYear();

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

        // Ambil webhost beserta relasi yang relevan untuk laporan tahunan ini.
        // Setelah semua relasi termuat, pengelompokan per bulan dilakukan di memory
        // agar sumber data tetap konsisten dari model Webhost dan relasinya.
        $webhosts = Webhost::query()
            ->with([
                'csMainProjects' => function ($query) use ($yearStart, $yearEnd) {
                    $query->select('id', 'id_webhost', 'jenis', 'tgl_masuk', 'dibayar')
                        ->where('jenis', 'Perpanjangan')
                        ->whereBetween('tgl_masuk', [
                            $yearStart->copy()->subMonth()->toDateString(),
                            $yearEnd->copy()->addMonth()->toDateString(),
                        ]);
                },
                'csMainProjects.transaksi_masuk' => function ($query) use ($yearStart, $yearEnd) {
                    $query->select('id_transaksi_masuk', 'id', 'tgl', 'bayar', 'pelunasan')
                        ->whereBetween('tgl', [
                            $yearStart->copy()->subMonth()->toDateString(),
                            $yearEnd->copy()->addMonth()->toDateString(),
                        ]);
                },
                'whmcs_domain' => function ($query) use ($yearStart, $yearEnd) {
                    $query->select('id', 'webhost_id', 'status', 'expirydate', 'domain')
                        ->whereBetween('expirydate', [
                            $yearStart->toDateString(),
                            $yearEnd->copy()->addYear()->toDateString(),
                        ]);
                },
                'perpanjang_terakhir',
                'pembuatan',
            ])
            ->where(function ($query) use ($yearStart, $yearEnd, $yearEndNext) {
                $query->whereHas('csMainProjects', function ($projectQuery) use ($yearStart, $yearEnd) {
                    $projectQuery->whereBetween('tgl_masuk', [
                        $yearStart->toDateString(),
                        $yearEnd->toDateString(),
                    ]);
                })->orWhereHas('whmcs_domain', function ($domainQuery) use ($yearStart, $yearEndNext) {
                    $domainQuery->whereBetween('expirydate', [
                        $yearStart->toDateString(),
                        $yearEndNext->toDateString(),
                    ]);
                });
            })
            ->get();

        $data = [];
        foreach ($months as $month) {
            $monthStart = Carbon::create($month['year'], $month['month'], 1)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $previousMonthStart = $monthStart->copy()->subMonth()->startOfMonth();
            $previousMonthEnd = $monthStart->copy()->subMonth()->endOfMonth();

            // Basis bulan laporan diambil dari bulan expiry domain WHMCS.
            // Webhost hanya dihitung jika sudah ada pada atau sebelum tahun laporan,
            // agar webhost baru di tahun berikutnya tidak ikut masuk bucket.
            $webhostsBulanIni = $webhosts->filter(function ($webhost) use ($monthStart, $monthEnd, $year) {
                if (! $webhost->whmcs_domain || empty($webhost->whmcs_domain->expirydate)) {
                    return false;
                }

                $tahunMulai = null;
                if (! empty($webhost->tgl_mulai)) {
                    $tahunMulai = Carbon::parse($webhost->tgl_mulai)->year;
                } elseif ($webhost->pembuatan && ! empty($webhost->pembuatan->tgl_masuk)) {
                    $tahunMulai = Carbon::parse($webhost->pembuatan->tgl_masuk)->year;
                }

                if ($tahunMulai && $tahunMulai > $year) {
                    return false;
                }

                $expiryDate = Carbon::parse($webhost->whmcs_domain->expirydate);
                return (int) $expiryDate->month === (int) $monthStart->month;
            });

            // Bentuk semua data pembayaran perpanjangan dari relasi Webhost -> CsMainProjects
            // -> transaksi_masuk. Jika transaksi belum ada, fallback ke tgl_masuk + dibayar project.
            $webhostsDenganPembayaran = $webhostsBulanIni->map(function ($webhost) {
                $paymentEntries = $webhost->csMainProjects
                    ->flatMap(function ($project) {
                        $entries = collect();

                        if ($project->transaksi_masuk && $project->transaksi_masuk->isNotEmpty()) {
                            $entries = $project->transaksi_masuk
                                ->filter(function ($transaksi) {
                                    return ! empty($transaksi->tgl) && (int) $transaksi->bayar > 0;
                                })
                                ->map(function ($transaksi) use ($project) {
                                    return [
                                        'project_id' => $project->id,
                                        'payment_date' => Carbon::parse($transaksi->tgl),
                                        'amount' => (int) $transaksi->bayar,
                                    ];
                                });
                        }

                        if ($entries->isEmpty() && ! empty($project->tgl_masuk) && (int) $project->dibayar > 0) {
                            $entries = collect([
                                [
                                    'project_id' => $project->id,
                                    'payment_date' => Carbon::parse($project->tgl_masuk),
                                    'amount' => (int) $project->dibayar,
                                ]
                            ]);
                        }

                        return $entries;
                    })
                    ->values();

                $webhost->setAttribute('renewal_payment_entries', $paymentEntries);
                return $webhost;
            });

            // Webhost dianggap "perpanjang" jika expiry month sama dengan bucket bulan,
            // tetapi expiry year sudah melewati tahun laporan. Contoh:
            // laporan 2026, expiry 2027-04-28 => berarti renewal April 2026 sudah berhasil.
            $perpanjangWebhosts = $webhostsDenganPembayaran->filter(function ($webhost) use ($year) {
                if (! $webhost->whmcs_domain || empty($webhost->whmcs_domain->expirydate)) {
                    return false;
                }

                $expiryDate = Carbon::parse($webhost->whmcs_domain->expirydate);
                return (int) $expiryDate->year > (int) $year;
            });

            // Webhost dianggap "tidak_perpanjang" jika expiry month sama dengan bucket bulan,
            // expiry year masih sama dengan tahun laporan, dan status domain sudah Expired.
            $tidakPerpanjangWebhosts = $webhostsDenganPembayaran->filter(function ($webhost) use ($year) {
                if (! $webhost->whmcs_domain || empty($webhost->whmcs_domain->expirydate)) {
                    return false;
                }

                $expiryDate = Carbon::parse($webhost->whmcs_domain->expirydate);

                return (int) $expiryDate->year === (int) $year
                    && optional($webhost->whmcs_domain)->status === 'Expired';
            });

            $perpanjang = $perpanjangWebhosts->count();
            $tidak_perpanjang = $tidakPerpanjangWebhosts->count();
            $total = $perpanjang + $tidak_perpanjang;
            $ratio = $total > 0 ? round(($perpanjang / $total) * 100, 2) : 0;

            $paymentEntries = $perpanjangWebhosts
                ->flatMap(function ($webhost) {
                    return collect($webhost->renewal_payment_entries)->map(function ($entry) use ($webhost) {
                        $entry['webhost_id'] = $webhost->id_webhost;
                        return $entry;
                    });
                })
                ->values();

            // PPJ dari bulan ini:
            // pembayaran perpanjangan yang bulan bayarnya sama dengan bulan expiry.
            $ppjDariBulanIniEntries = $paymentEntries->filter(function ($entry) use ($monthStart, $monthEnd) {
                return $entry['payment_date']->between($monthStart, $monthEnd);
            });

            // PPJ dari bulan lain:
            // pembayaran perpanjangan untuk webhost bulan ini, tetapi dibayar
            // bukan pada bulan expiry-nya.
            $ppjDariBulanLainEntries = $paymentEntries->filter(function ($entry) use ($monthStart, $monthEnd) {
                return ! $entry['payment_date']->between($monthStart, $monthEnd);
            });

            // PPJ bulan ini yang terbayar di bulan lalu:
            // subset dari PPJ bulan lain yang dibayar tepat pada bulan sebelumnya.
            $ppjBulanIniTerbayarBulanLaluEntries = $paymentEntries->filter(function ($entry) use ($previousMonthStart, $previousMonthEnd) {
                return $entry['payment_date']->between($previousMonthStart, $previousMonthEnd);
            });

            $ppjDariBulanIni = $ppjDariBulanIniEntries
                ->pluck('webhost_id')
                ->unique()
                ->count();

            $ppjDariBulanLain = $ppjDariBulanLainEntries
                ->pluck('webhost_id')
                ->unique()
                ->count();

            $ppjBulanIniTerbayarBulanLalu = $ppjBulanIniTerbayarBulanLaluEntries
                ->pluck('webhost_id')
                ->unique()
                ->count();

            // Nominal umum perpanjangan untuk semua webhost yang masuk bucket bulan ini.
            $nominalPerpanjang = $paymentEntries->sum('amount');
            $perpanjangTermahal = $paymentEntries->max('amount') ?? 0;
            $rataRataBiayaPerpanjang = $perpanjang > 0
                ? round($nominalPerpanjang / $perpanjang, 2)
                : 0;

            $data[] = [
                'month' => $month['name'],
                'year' => $month['year'],
                'perpanjang' => $perpanjang,
                'tidak_perpanjang' => $tidak_perpanjang,
                'total' => $total,
                'ratio' => $ratio,
                'rincian' => [
                    'Total Webhost' => $total,
                    'Webhost Perpanjang' => $perpanjang,
                    'Webhost Tidak Perpanjang' => $tidak_perpanjang,
                    'Ratio Perpanjang (%)' => $ratio,
                    'Total Pemasukkan Perpanjang' => $nominalPerpanjang,
                    'PPJ dari bulan ini' => $ppjDariBulanIni,
                    'PPJ dari bulan lain' => $ppjDariBulanLain,
                    'PPJ bulan ini yang terbayar di bulan lalu' => $ppjBulanIniTerbayarBulanLalu,
                    'Rata-rata biaya perpanjang' => $rataRataBiayaPerpanjang,
                    'Perpanjang termahal' => $perpanjangTermahal,
                ],
            ];
        }

        return response()->json([
            'year' => $year,
            'months' => $months,
            'data' => $data,
        ]);
    }
}
