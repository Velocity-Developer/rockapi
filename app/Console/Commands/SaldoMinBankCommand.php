<?php

namespace App\Console\Commands;

use App\Models\Bank;
use App\Models\SaldoBank;
use App\Models\User;
use App\Notifications\SaldoMinManAdvNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use App\Services\TelegramServices;

class SaldoMinBankCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:saldo-min-bank-command
        {--bulan= : Bulan yang dicek, format Y-m}
        {--bank= : Nama bank yang dicek}
        {--minimum=10000000 : Batas minimum saldo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek saldo minimum 10juta dan notifikasi';

    /**
     * Execute the console command.
     */
    public function handle(TelegramServices $telegramServices)
    {
        $bulan = $this->option('bulan') ?: date('Y-m');
        $bank = $this->option('bank') ?: 'jago';
        $minimum = (int) $this->option('minimum') ?: 1000000000;

        $banks = $bank ? collect([$bank]) : $this->getBanks($bulan);

        if ($banks->isEmpty()) {
            $this->info('Tidak ada data bank untuk bulan ' . $bulan . '.');

            return self::SUCCESS;
        }

        $users = User::role('manager_advertising')->get();

        if ($users->isEmpty()) {
            $this->warn('Tidak ada user dengan role manager_advertising untuk menerima notifikasi.');
        }
        $telegramId = $users->pluck('telegram_id')->filter()->pluck('id');

        $saldoMinimum = [];

        foreach ($banks as $namaBank) {
            $saldoSaatIni = $this->getSaldoSaatIni($bulan, $namaBank);

            $this->line($namaBank . ' - saldo saat ini: ' . $this->formatRupiah($saldoSaatIni));

            if ($saldoSaatIni <= $minimum) {
                $saldoMinimum[] = [
                    'bank' => $namaBank,
                    'saldo' => $saldoSaatIni,
                ];

                if ($users->isNotEmpty()) {
                    $telegramServices->sendMessage('787473227', $namaBank . ' - saldo saat ini: ' . $this->formatRupiah($saldoSaatIni));
                }
            }
        }

        if (empty($saldoMinimum)) {
            $this->info('Semua saldo bank masih di atas minimum ' . $this->formatRupiah($minimum) . '.');

            return self::SUCCESS;
        }

        $this->warn(count($saldoMinimum) . ' bank memiliki saldo minimum.');
        $this->table(
            ['Bank', 'Saldo Saat Ini'],
            collect($saldoMinimum)->map(fn($item) => [
                $item['bank'],
                $this->formatRupiah($item['saldo']),
            ])
        );

        return self::SUCCESS;
    }

    private function getBanks(string $bulan)
    {
        return SaldoBank::where('bulan', $bulan)
            ->pluck('bank')
            ->merge(
                Bank::where('tgl', 'like', $bulan . '%')->pluck('bank')
            )
            ->filter()
            ->unique()
            ->values();
    }

    private function getSaldoSaatIni(string $bulan, string $bank): float
    {
        $saldoBank = SaldoBank::where('bulan', $bulan)
            ->where('bank', $bank)
            ->first();

        $saldo = $saldoBank->nominal ?? 0;

        $transaksi = Bank::where('tgl', 'like', $bulan . '%')
            ->where('bank', $bank)
            ->orderBy('tgl', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($transaksi as $bankTransaksi) {
            if ($bankTransaksi->jenis_transaksi == 'masuk') {
                $saldo += $bankTransaksi->nominal;
            } else {
                $saldo -= $bankTransaksi->nominal;
            }
        }

        return $saldo;
    }

    private function formatRupiah(float|int $nominal): string
    {
        return 'Rp ' . number_format($nominal, 0, ',', '.');
    }
}
