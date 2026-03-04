<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhmcsDomain;
use App\Services\WHMCSCustomService;
use Illuminate\Support\Facades\Log;

class WhmcsSyncDomainExpired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whmcs:sync-domain-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize expired domains from WHMCS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // mengambil data domain yang sudah expired dari WHMCS
        $domains = (new WHMCSCustomService())->getDomainsExpiry();

        //if success = false
        if (isset($domains['success']) && $domains['success'] === false) {
            Log::error('Failed to synchronize domain expired: ' . $domains['message']);
            $this->info('Failed to synchronize domain expired.');
            return;
        }

        $domains = $domains['data'] ?? [];

        // menyimpan data domain ke tabel whmcs_domains
        foreach ($domains as $domain) {
            WhmcsDomain::updateOrCreate(
                ['whmcs_id' => $domain['id']],
                $domain
            );
        }

        $this->info('Domain expired synchronized successfully.');
    }
}
