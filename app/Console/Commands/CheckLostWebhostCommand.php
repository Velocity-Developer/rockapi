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
    protected $signature = 'csmainproject:check-lost-webhost {jenis? : Filter berdasarkan jenis project (CS/PM/WM)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengecek data CsMainProject yang memiliki id_webhost yang tidak ada di tabel tb_webhost (opsional filter berdasarkan jenis)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jenis = $this->argument('jenis');

        $this->info('Mengecek data CsMainProject yang kehilangan referensi Webhost' . ($jenis ? " untuk jenis: $jenis" : "") . '...');

        $query = CsMainProject::whereDoesntHave('webhost');

        if ($jenis) {
            $query->where('jenis', $jenis);
        }

        $orphans = $query->get();

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
