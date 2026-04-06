<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $files = glob(storage_path('logs/*.log'));
        foreach ($files as $file) {
            unlink($file);
        }
        $this->info('Logs have been cleared!');
    }
}
