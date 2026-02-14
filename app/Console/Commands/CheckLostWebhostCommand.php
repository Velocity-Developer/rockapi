<?php

namespace App\Console\Commands;

use App\Models\CsMainProject;
use Illuminate\Console\Command;

class CheckLostWebhostCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csmainproject:check-lost-webhost';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengecek data CsMainProject yang memiliki id_webhost yang tidak ada di tabel tb_webhost';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mengecek data CsMainProject yang kehilangan referensi Webhost...');

        $orphans = CsMainProject::whereDoesntHave('webhost')->get();

        if ($orphans->isEmpty()) {
            $this->info('Tidak ditemukan data CsMainProject yang kehilangan referensi Webhost.');
            return 0;
        }

        $this->warn('Ditemukan ' . $orphans->count() . ' data CsMainProject yang kehilangan referensi Webhost:');

        $headers = ['ID', 'Jenis', 'Deskripsi', 'ID Webhost (Missing)'];
        $data = $orphans->map(function ($project) {
            return [
                $project->id,
                $project->jenis,
                $project->deskripsi,
                $project->id_webhost,
            ];
        });

        $this->table($headers, $data);

        return 0;
    }
}
