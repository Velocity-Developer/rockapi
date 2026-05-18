<?php

namespace App\Observers;

use App\Models\Bank;
use App\Models\SaldoBank;
use App\Models\User;
use App\Services\TelegramServices;

class BankObserver
{
    private const MINIMUM_SALDO = 10000000;

    /**
     * Handle the Bank "created" event.
     */
    public function created(Bank $bank): void
    {
        $bank->syncJenisPivots();
        $this->sendSaldoMinimumNotification($bank);
    }

    /**
     * Handle the Bank "updated" event.
     */
    public function updated(Bank $bank): void
    {
        if ($bank->wasChanged('jenis')) {
            $bank->syncJenisPivots();
        }
    }

    /**
     * Handle the Bank "deleted" event.
     */
    public function deleted(Bank $bank): void
    {
        // Hapus semua relasi pivot saat Bank dihapus
        $bank->CsMainProject()->detach();
        $bank->TransaksiKeluar()->detach();
    }

    /**
     * Handle the Bank "restored" event.
     */
    public function restored(Bank $bank): void
    {
        //
    }

    /**
     * Handle the Bank "force deleted" event.
     */
    public function forceDeleted(Bank $bank): void
    {
        //
    }

    private function sendSaldoMinimumNotification(Bank $bank): void
    {
        if (! $bank->bank) {
            return;
        }

        //pastikan bank = jago
        if ($bank->jenis != 'jago') {
            return;
        }

        $bulan = date('Y-m');
        $saldoSaatIni = $this->getSaldoSaatIni($bulan, $bank->bank);

        if ($saldoSaatIni >= self::MINIMUM_SALDO) {
            return;
        }

        $telegramServices = app(TelegramServices::class);

        $message = $bank->bank
            . ' - saldo saat ini: '
            . $this->formatRupiah($saldoSaatIni)
            . ', kurang dari '
            . $this->formatRupiah(self::MINIMUM_SALDO);

        $users = User::role('manager_advertising')
            ->whereNotNull('telegram_id')
            ->where('telegram_id', '!=', '')
            ->get();

        if ($users->isEmpty()) {
            $telegramServices->sendMessage('787473227', $message);
            return;
        }

        foreach ($users as $user) {
            $telegramServices->sendMessage($user->telegram_id, $message);
        }
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
