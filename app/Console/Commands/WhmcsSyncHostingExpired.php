<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WHMCSSyncServices;
use App\Models\WhmcsHosting;
use Illuminate\Support\Facades\Log;

class WhmcsSyncHostingExpired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whmcs:sync-hosting-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize expired hostings from WHMCS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // mengambil data hosting expired dari WHMCS
        $hostings = (new WHMCSSyncServices())->syncHostingExpired();

        //if hostings = 0
        if ($hostings === 0) {
            Log::info('No hosting expired synchronized.');
            $this->info('No hosting expired synchronized.');
            return;
        }

        $this->info('Hosting expired synchronized successfully.');
    }
}
