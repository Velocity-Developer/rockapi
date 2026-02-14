<?php

namespace App\Console\Commands;

use App\Models\Webhost;
use Illuminate\Console\Command;

class WebhostLostCsMainProjectCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhost:lost-csmainproject';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengecek data Webhost yang tidak memiliki referensi di tabel tb_cs_main_project';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mengecek data Webhost yang tidak memiliki project di CsMainProject...');

        $orphans = Webhost::whereDoesntHave('csMainProjects')->get();

        if ($orphans->isEmpty()) {
            $this->info('Tidak ditemukan data Webhost yang tidak memiliki project.');
            return 0;
        }

        $this->warn('Ditemukan ' . $orphans->count() . ' data Webhost yang tidak memiliki project:');

        $headers = ['ID Webhost', 'Nama Web', 'HP', 'Tanggal Mulai'];
        $data = $orphans->map(function ($webhost) {
            return [
                $webhost->id_webhost,
                $webhost->nama_web,
                $webhost->hp,
                $webhost->tgl_mulai,
            ];
        });

        $this->table($headers, $data);

        return 0;
    }
}
