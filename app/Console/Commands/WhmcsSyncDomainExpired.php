<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhmcsDomain;
use App\Services\WHMCSSyncServices;
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
        $domains = (new WHMCSSyncServices())->syncDomainExpired();

        //if domains = 0
        if ($domains === 0) {
            Log::info('No domain expired synchronized.');
            $this->info('No domain expired synchronized.');
            return;
        }

        $this->info('Domain expired synchronized successfully.');
    }
}
