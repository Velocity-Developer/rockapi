<?php

namespace App\Console\Commands;

use App\Models\Webhost;
use App\Models\WebhostSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateWebhostDomainSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhost:generate-domain-subscriptions
                            {--dry-run : Tampilkan hasil tanpa menyimpan data}
                            {--chunk=50 : Jumlah webhost yang diproses per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate lifecycle subscription domain dari Webhost yang memiliki relasi WHMCS Domain';

    /**
     * Jenis project pembuatan yang dipakai sebagai referensi awal.
     *
     * @var array<int, string>
     */
    protected array $jenisPembuatan = [
        'Pembuatan',
        'Pembuatan apk',
        'Pembuatan apk custom',
        'Pembuatan Tanpa Domain',
        'Pembuatan Tanpa Hosting',
        'Pembuatan Tanpa Domain+Hosting',
        'Pembuatan web konsep',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max((int) $this->option('chunk'), 1);

        $this->info("Mencari webhost yang punya WHMCS domain dan belum punya subscription domain. Batch: {$chunkSize}");

        $baseQuery = Webhost::with([
            'whmcs_domain',
            'csMainProjects' => function ($query) {
                $query->whereIn('jenis', array_merge($this->jenisPembuatan, ['Perpanjangan']))
                    ->orderBy('tgl_masuk', 'asc')
                    ->orderBy('id', 'asc');
            },
        ])
            ->whereHas('whmcs_domain', function ($query) {
                $query->whereNotNull('webhost_id');
            })
            ->whereHas('csMainProjects', function ($query) {
                $query->whereIn('jenis', array_merge($this->jenisPembuatan, ['Perpanjangan']))
                    ->whereNotNull('tgl_masuk')
                    ->where('tgl_masuk', '!=', '0000-00-00');
            })
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('service_type', 'domain');
            })
            ->orderBy('id_webhost');

        if (! (clone $baseQuery)->exists()) {
            $this->warn('Tidak ada webhost yang cocok untuk digenerate.');
            return self::SUCCESS;
        }

        $createdRows = 0;
        $processedWebhosts = 0;
        $rowsPreview = [];
        $batchNumber = 0;

        $baseQuery->chunkById($chunkSize, function ($webhosts) use (
            $dryRun,
            &$createdRows,
            &$processedWebhosts,
            &$rowsPreview,
            &$batchNumber
        ) {
            $batchNumber++;
            $this->info("Memproses batch {$batchNumber} ({$webhosts->count()} webhost)...");

            $periodeMonths = 12; // default 1 tahun
            $nowDate = Carbon::now();

            foreach ($webhosts as $webhost) {
                $domain = $webhost->whmcs_domain;

                // Jika tidak punya domain, skip.
                if (! $domain) {
                    continue;
                }

                $rows = [];

                $registrationDate = $domain->registrationdate ? Carbon::parse($domain->registrationdate)->startOfDay() : null;
                $expiryDomain = $domain->expirydate ? Carbon::parse($domain->expirydate)->startOfDay() : null;
                $statusDomain = $domain->status ?: null;
                $firstProjectDate = null;

                // Ambil anchor tanggal-bulan dari registerdate_domain
                $anchor = Carbon::parse($domain->registrationdate);

                $startDateLifecycle = $domain->registrationdate ? Carbon::parse($domain->registrationdate)->startOfDay() : null;

                /**
                 * Step 1. Cari project pembuatan awal sebagai acuan utama.
                 * jika tidak ada, lanjutkan dengan project perpanjangan pertama.
                 */
                $pembuatanProject = $webhost->csMainProjects
                    ->first(fn($project) => in_array($project->jenis, $this->jenisPembuatan, true) && $this->resolveComparableDate($project->tgl_masuk) !== null);

                if ($pembuatanProject) {

                    $firstProjectDate = $pembuatanProject->tgl_masuk ? Carbon::parse($pembuatanProject->tgl_masuk)->startOfDay() : null;

                    // Subscription awal mengikuti lifecycle domain, bukan tahun pembayaran project.
                    $startDate = $startDateLifecycle?->copy();

                    if (! $startDate) {
                        continue;
                    }

                    // Hitung end_date
                    $endDate = $startDate->copy()->addMonths($periodeMonths);

                    //jika ada tambahkan dalam row preview
                    $rows[] = [
                        'webhost_id' => $pembuatanProject->id_webhost,
                        'cs_main_project_id' => $pembuatanProject->id,
                        'parent_subscription_id' => null,
                        'source_type' => 'csmainproject',
                        'service_type' => 'domain',
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'nextduedate' => $endDate->toDateString(),
                        'payment_status' => $this->resolvePaymentStatus(
                            $pembuatanProject?->dibayar,
                            $pembuatanProject?->biaya ?? $pembuatanProject?->dibayar
                        ),
                        'paid_at' => $this->resolvePaidAt($pembuatanProject?->tgl_masuk),
                        'status' => $endDate->greaterThan($nowDate) ? 'active' : 'expired',
                    ];

                    $startDateLifecycle = $endDate->copy();
                }

                /**
                 * Step 2.
                 * Bentuk renewal secara berantai.
                 *
                 * Aturan:
                 * - paid_at = tgl_masuk project renewal
                 * - start_date = end_date subscription sebelumnya
                 * - end_date = start_date + 1 tahun
                 *
                 * Jadi kalau klien bayar 2 bulan sebelum expired:
                 * - paid_at tetap bulan pembayaran
                 * - periode baru tetap dimulai dari akhir periode lama
                 */

                $renewalProjects = $webhost->csMainProjects
                    ->filter(fn($project) => $project->jenis === 'Perpanjangan')
                    ->values();

                foreach ($renewalProjects as $project) {
                    //cek jika tidak ada tgl_masuk, skip.
                    $projectPaidAt = $this->resolveComparableDate($project->tgl_masuk);
                    if (! $projectPaidAt) {
                        continue;
                    }

                    //jika pembuatan tidak ada, gunakan perpanjangan pertama.
                    if (! $pembuatanProject) {
                        $firstProjectDate = $project->tgl_masuk ? Carbon::parse($project->tgl_masuk)->startOfDay() : null;
                    }

                    // Renewal selalu dimulai dari akhir subscription sebelumnya,
                    // jadi pembayaran lebih awal tetap masuk ke bulan renewal yang benar.
                    $startDate = $startDateLifecycle?->copy();

                    if (! $startDate) {
                        continue;
                    }

                    // Hitung end_date
                    $endDate = $startDate->copy()->addMonths($periodeMonths);

                    //tambahkan ke row
                    $rows[] = [
                        'webhost_id' => $project->id_webhost,
                        'cs_main_project_id' => $project->id,
                        'parent_subscription_id' => null,
                        'source_type' => 'csmainproject',
                        'service_type' => 'domain',
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'nextduedate' => $endDate->toDateString(),
                        'payment_status' => $this->resolvePaymentStatus(
                            $project?->dibayar,
                            $project?->biaya ?? $project?->dibayar
                        ),
                        'paid_at' => $this->resolvePaidAt($project?->tgl_masuk),
                        'status' => $endDate->greaterThan($nowDate) ? 'active' : 'expired',
                    ];

                    $startDateLifecycle = $endDate->copy();
                }

                if (! empty($rows)) {
                    $lastRowIndex = array_key_last($rows);
                    $rows[$lastRowIndex]['status'] = $this->resolveStatus($rows[$lastRowIndex]['end_date']);
                }

                $processedWebhosts++;
                $rowsPreview[] = [
                    $webhost->id_webhost,
                    $webhost->nama_web,
                    $firstProjectDate->toDateString(),
                    $registrationDate->toDateString(),
                    $expiryDomain?->toDateString(),
                    $anchor->month . '-' . $anchor->day,
                    count($rows),
                ];

                if ($dryRun) {
                    $createdRows += count($rows);
                    continue;
                }

                // simpan subscription
                $parentId = null;
                foreach ($rows as $row) {
                    $existingSubscription = WebhostSubscription::where('webhost_id', $row['webhost_id'])
                        ->where('service_type', $row['service_type'])
                        ->where('cs_main_project_id', $row['cs_main_project_id'])
                        ->first();

                    if ($existingSubscription) {
                        $parentId = $existingSubscription->id;
                        continue;
                    }

                    $row['parent_subscription_id'] = $parentId;
                    $subscription = WebhostSubscription::create($row);
                    $parentId = $subscription->id;
                    $createdRows++;
                }
            }
        }, 'id_webhost', 'id_webhost');

        if (! empty($rowsPreview)) {
            $this->table(
                ['ID Webhost', 'Nama Web', 'First Project Date', 'Registration Date', 'Expiry Date', 'Month Renewal', 'Rows'],
                $rowsPreview
            );
        }

        $summaryMessage = $dryRun
            ? "Dry run selesai. {$processedWebhosts} webhost diproses, {$createdRows} webhost_subscriptions akan dibuat."
            : "Generate selesai. {$processedWebhosts} webhost diproses, {$createdRows} webhost_subscriptions dibuat.";

        $this->info($summaryMessage);

        return self::SUCCESS;
    }

    private function resolveStatus(string $endDate): string
    {
        return Carbon::parse($endDate)->lt(now()->startOfDay()) ? 'expired' : 'active';
    }

    private function resolvePaymentStatus($dibayar, $biaya): string
    {
        $dibayar = (int) ($dibayar ?? 0);
        $biaya = (int) ($biaya ?? 0);

        if ($dibayar <= 0) {
            return 'unpaid';
        }

        if ($biaya > 0 && $dibayar < $biaya) {
            return 'partial';
        }

        return 'paid';
    }

    private function resolvePaidAt($value): ?string
    {
        if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $th) {
            return null;
        }
    }

    private function resolveWhmcsMismatch(?string $providerStatus, ?Carbon $providerExpiryDate, string $localEndDate): bool
    {
        $normalizedStatus = strtolower(trim((string) $providerStatus));
        $localEndDate = Carbon::parse($localEndDate)->startOfDay();

        if ($normalizedStatus === 'expired' && $localEndDate->gte(now()->startOfDay())) {
            return true;
        }

        if ($providerExpiryDate && $providerExpiryDate->lt($localEndDate)) {
            return true;
        }

        return false;
    }

    private function resolveComparableDate($value): ?Carbon
    {
        if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $th) {
            return null;
        }
    }

    private function isRenewalAnomaly(Carbon $projectPaidAt, Carbon $previousEndDate): bool
    {
        // Warning only:
        // pembayaran renewal yang terlalu jauh dari akhir periode sebelumnya
        // biasanya menandakan data internal tidak rapi, tetapi tetap kita catat.
        return abs($projectPaidAt->diffInDays($previousEndDate, false)) > 370;
    }
}
