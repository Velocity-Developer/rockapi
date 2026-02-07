<?php

namespace App\Console\Commands;

use App\Services\ConvertDataLamaService;
use Illuminate\Console\Command;

class ConvertKonversiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:data-konversi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrasi data dari tabel lama (tb_konversi, tb_konversi_display, tb_konversi_wa5, tb_konversi_organik) ke tabel konversi';

    public function __construct(
        protected ConvertDataLamaService $convertService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai migrasi data konversi...');

        try {
            $result = $this->convertService->handle_konversi();

            $this->info('Migrasi data konversi berhasil!');
            $this->info('Total data dimigrasi: '.count($result));
        } catch (\Exception $e) {
            $this->error('Terjadi kesalahan: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
