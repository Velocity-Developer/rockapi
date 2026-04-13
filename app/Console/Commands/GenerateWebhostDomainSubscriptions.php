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

            foreach ($webhosts as $webhost) {
                $domain = $webhost->whmcs_domain;
                if (! $domain) {
                    continue;
                }

                $expiryDate = $domain->expirydate ? Carbon::parse($domain->expirydate)->startOfDay() : null;
                $providerStatus = $domain->status ?: null;

                /**
                 * Step 1.
                 * Cari project seed untuk memulai lifecycle internal.
                 *
                 * Prioritas:
                 * - project pembuatan
                 * - fallback ke project pertama yang punya tgl_masuk valid
                 *
                 * tgl_masuk project seed dipakai sebagai:
                 * - paid_at pertama
                 * - start_date subscription pertama
                 */
                $pembuatanProject = $webhost->csMainProjects
                    ->first(fn($project) => in_array($project->jenis, $this->jenisPembuatan, true));

                $seedProject = $pembuatanProject
                    ?: $webhost->csMainProjects->first(fn($project) => $this->resolveComparableDate($project->tgl_masuk) !== null);

                $seedPaidAt = $this->resolveComparableDate($seedProject?->tgl_masuk);
                if (! $seedPaidAt) {
                    continue;
                }

                $renewalProjects = $webhost->csMainProjects
                    ->filter(fn($project) => $project->jenis === 'Perpanjangan')
                    ->values();

                $rows = [];
                $currentStartDate = $seedPaidAt->copy();
                $currentEndDate = $currentStartDate->copy()->addYear();

                $rows[] = [
                    'webhost_id' => $webhost->id_webhost,
                    'cs_main_project_id' => $seedProject?->id,
                    'parent_subscription_id' => null,
                    'source_type' => $seedProject ? 'csmainproject' : 'manual',
                    'service_type' => 'domain',
                    'start_date' => $currentStartDate->toDateString(),
                    'end_date' => $currentEndDate->toDateString(),
                    'renewed_from_date' => null,
                    'status' => 'expired',
                    'payment_status' => $this->resolvePaymentStatus(
                        $seedProject?->dibayar,
                        $seedProject?->biaya ?? $seedProject?->dibayar
                    ),
                    'paid_at' => $this->resolvePaidAt($seedProject?->tgl_masuk),
                    'provider_status' => $providerStatus,
                    'provider_expiry_date' => $expiryDate?->toDateString(),
                    'is_whmcs_mismatch' => false,
                    'nominal' => $seedProject?->dibayar ?? 0,
                    'description' => $seedProject?->deskripsi ?: 'Generated from CsMainProject tgl_masuk',
                ];

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
                foreach ($renewalProjects as $project) {
                    $projectPaidAt = $this->resolveComparableDate($project->tgl_masuk);
                    if (! $projectPaidAt) {
                        continue;
                    }

                    if ($this->isRenewalAnomaly($projectPaidAt, $currentEndDate)) {
                        $this->warn(
                            "Skip anomali webhost {$webhost->id_webhost} ({$webhost->nama_web}) " .
                                "project {$project->id} tgl_masuk={$project->tgl_masuk} " .
                                "terdeteksi jauh dari akhir periode sebelumnya, tetapi tetap dicatat."
                        );
                    }

                    $renewedFromDate = $currentEndDate->copy();
                    $nextStartDate = $currentEndDate->copy();
                    $nextEndDate = $nextStartDate->copy()->addYear();

                    $rows[] = [
                        'webhost_id' => $webhost->id_webhost,
                        'cs_main_project_id' => $project->id,
                        'parent_subscription_id' => null,
                        'source_type' => 'csmainproject',
                        'service_type' => 'domain',
                        'start_date' => $nextStartDate->toDateString(),
                        'end_date' => $nextEndDate->toDateString(),
                        'renewed_from_date' => $renewedFromDate->toDateString(),
                        'status' => 'expired',
                        'payment_status' => $this->resolvePaymentStatus($project->dibayar, $project->biaya),
                        'paid_at' => $this->resolvePaidAt($project->tgl_masuk),
                        'provider_status' => $providerStatus,
                        'provider_expiry_date' => $expiryDate?->toDateString(),
                        'is_whmcs_mismatch' => false,
                        'nominal' => $project->dibayar ?? 0,
                        'description' => $project->deskripsi,
                    ];

                    $currentStartDate = $nextStartDate->copy();
                    $currentEndDate = $nextEndDate->copy();
                }

                /**
                 * Step 3.
                 * Data WHMCS hanya dipakai sebagai snapshot provider:
                 * - boleh memperpanjang end_date jika provider lebih maju
                 * - tidak boleh memundurkan lifecycle internal
                 */
                if ($expiryDate && ! empty($rows)) {
                    $lastRowIndex = array_key_last($rows);
                    $localEndDate = Carbon::parse($rows[$lastRowIndex]['end_date'])->startOfDay();

                    // WHMCS hanya boleh memperpanjang end date, bukan memundurkan
                    // periode yang sudah dihitung dari histori renewal lokal.
                    if ($expiryDate->gte($localEndDate)) {
                        $rows[$lastRowIndex]['end_date'] = $expiryDate->toDateString();
                    }
                }

                if (! empty($rows)) {
                    $lastRowIndex = array_key_last($rows);
                    $rows[$lastRowIndex]['status'] = $this->resolveStatus($rows[$lastRowIndex]['end_date']);
                    $rows[$lastRowIndex]['is_whmcs_mismatch'] = $this->resolveWhmcsMismatch(
                        $providerStatus,
                        $expiryDate,
                        $rows[$lastRowIndex]['end_date']
                    );
                }

                $processedWebhosts++;
                if (count($rowsPreview) < 20) {
                    $rowsPreview[] = [
                        $webhost->id_webhost,
                        $webhost->nama_web,
                        $seedPaidAt->toDateString(),
                        $expiryDate?->toDateString(),
                        count($rows),
                    ];
                }

                if ($dryRun) {
                    $createdRows += count($rows);
                    continue;
                }

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
                ['ID Webhost', 'Nama Web', 'Registration Date', 'Expiry Date', 'Rows'],
                $rowsPreview
            );
        }

        $summaryMessage = $dryRun
            ? "Dry run selesai. {$processedWebhosts} webhost diproses, {$createdRows} row akan dibuat."
            : "Generate selesai. {$processedWebhosts} webhost diproses, {$createdRows} row dibuat.";

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
