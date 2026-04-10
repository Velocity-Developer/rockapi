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
    protected $signature = 'webhost:generate-domain-subscriptions {--dry-run : Tampilkan hasil tanpa menyimpan data}';

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

        $this->info('Mencari webhost yang punya WHMCS domain dan registration date...');

        $webhosts = Webhost::with([
            'whmcs_domain',
            'csMainProjects' => function ($query) {
                $query->whereIn('jenis', array_merge($this->jenisPembuatan, ['Perpanjangan']))
                    ->orderBy('tgl_masuk', 'asc')
                    ->orderBy('id', 'asc');
            },
            'subscriptions' => function ($query) {
                $query->where('service_type', 'domain')->orderBy('start_date');
            },
        ])
            ->whereHas('whmcs_domain', function ($query) {
                $query->whereNotNull('webhost_id')
                    ->whereNotNull('registrationdate');
            })
            ->orderBy('id_webhost')
            ->get();

        if ($webhosts->isEmpty()) {
            $this->warn('Tidak ada webhost yang cocok untuk digenerate.');
            return self::SUCCESS;
        }

        $createdRows = 0;
        $skippedWebhosts = 0;
        $processedWebhosts = 0;
        $rowsPreview = [];

        foreach ($webhosts as $webhost) {
            if ($webhost->subscriptions->isNotEmpty()) {
                $skippedWebhosts++;
                $this->line("Skip webhost {$webhost->id_webhost} ({$webhost->nama_web}) karena subscription domain sudah ada.");
                continue;
            }

            $domain = $webhost->whmcs_domain;
            if (! $domain || ! $domain->registrationdate) {
                $skippedWebhosts++;
                continue;
            }

            $registrationDate = Carbon::parse($domain->registrationdate)->startOfDay();
            $expiryDate = $domain->expirydate ? Carbon::parse($domain->expirydate)->startOfDay() : null;

            $pembuatanProject = $webhost->csMainProjects
                ->first(fn ($project) => in_array($project->jenis, $this->jenisPembuatan, true));

            $renewalProjects = $webhost->csMainProjects
                ->filter(fn ($project) => $project->jenis === 'Perpanjangan')
                ->values();

            $rows = [];
            $cursorStart = $registrationDate->copy();

            $rows[] = [
                'webhost_id' => $webhost->id_webhost,
                'cs_main_project_id' => $pembuatanProject?->id,
                'parent_subscription_id' => null,
                'source_type' => 'whmcs_domain',
                'service_type' => 'domain',
                'start_date' => $cursorStart->toDateString(),
                'end_date' => $cursorStart->copy()->addYear()->toDateString(),
                'renewed_from_date' => null,
                'status' => 'expired',
                'nominal' => $pembuatanProject?->dibayar ?? 0,
                'description' => 'Generated from WHMCS registration date',
            ];

            foreach ($renewalProjects as $project) {
                $renewedFromDate = $cursorStart->copy()->addYear();
                $nextEndDate = $renewedFromDate->copy()->addYear();

                $rows[] = [
                    'webhost_id' => $webhost->id_webhost,
                    'cs_main_project_id' => $project->id,
                    'parent_subscription_id' => null,
                    'source_type' => 'csmainproject',
                    'service_type' => 'domain',
                    'start_date' => $renewedFromDate->toDateString(),
                    'end_date' => $nextEndDate->toDateString(),
                    'renewed_from_date' => $renewedFromDate->toDateString(),
                    'status' => 'expired',
                    'nominal' => $project->dibayar ?? 0,
                    'description' => $project->deskripsi,
                ];

                $cursorStart = $renewedFromDate->copy();
            }

            if ($expiryDate && ! empty($rows)) {
                $rows[array_key_last($rows)]['end_date'] = $expiryDate->toDateString();
            }

            if (! empty($rows)) {
                $rows[array_key_last($rows)]['status'] = $this->resolveStatus($rows[array_key_last($rows)]['end_date']);
            }

            $processedWebhosts++;
            $rowsPreview[] = [
                $webhost->id_webhost,
                $webhost->nama_web,
                $registrationDate->toDateString(),
                $expiryDate?->toDateString(),
                count($rows),
            ];

            if ($dryRun) {
                $createdRows += count($rows);
                continue;
            }

            $parentId = null;
            foreach ($rows as $row) {
                $row['parent_subscription_id'] = $parentId;
                $subscription = WebhostSubscription::create($row);
                $parentId = $subscription->id;
                $createdRows++;
            }
        }

        if (! empty($rowsPreview)) {
            $this->table(
                ['ID Webhost', 'Nama Web', 'Registration Date', 'Expiry Date', 'Rows'],
                $rowsPreview
            );
        }

        $summaryMessage = $dryRun
            ? "Dry run selesai. {$processedWebhosts} webhost diproses, {$createdRows} row akan dibuat, {$skippedWebhosts} webhost dilewati."
            : "Generate selesai. {$processedWebhosts} webhost diproses, {$createdRows} row dibuat, {$skippedWebhosts} webhost dilewati.";

        $this->info($summaryMessage);

        return self::SUCCESS;
    }

    private function resolveStatus(string $endDate): string
    {
        return Carbon::parse($endDate)->lt(now()->startOfDay()) ? 'expired' : 'active';
    }
}
