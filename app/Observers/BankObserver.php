<?php

namespace App\Observers;

use App\Models\Bank;
use App\Models\SaldoBank;
use App\Models\User;
use App\Services\TelegramServices;

class BankObserver
{
    private const MINIMUM_SALDO_BY_BANK = [
        'jago' => [
            'label' => 'Jago',
            'min' => 10000000,
            'label_currency' => 'Rp',
        ],
        'vcc_jago_ads' => [
            'label' => 'VCC Jago Ads',
            'min' => 3000000,
            'label_currency' => 'Rp',
        ],
        'resellercamp' => [
            'label' => 'RESELLERCAMP',
            'min' => 100,
            'label_currency' => '$',
        ],
        'srsx' => [
            'label' => 'SRSX',
            'min' => 600000,
            'label_currency' => 'Rp',
        ],
    ];

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

        $minimumSaldoConfig = self::MINIMUM_SALDO_BY_BANK[$bank->bank] ?? null;
        $minimumSaldo = $minimumSaldoConfig['min'] ?? null;

        if (! $minimumSaldo) {
            return;
        }

        $bulan = date('Y-m');
        $saldoSaatIni = $this->getSaldoSaatIni($bulan, $bank->bank);

        if ($saldoSaatIni >= $minimumSaldo) {
            return;
        }

        $telegramServices = app(TelegramServices::class);

        $message = 'Saldo bank ' . $minimumSaldoConfig['label'] . ' saat ini kurang dari '
            . $this->formatCurrency($minimumSaldo, $minimumSaldoConfig['label_currency'])
            . "\n\nSaldo bank:\n"
            . $this->getSaldoBankMessage($bulan);

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

    private function getSaldoBankMessage(string $bulan): string
    {
        $messages = [];

        foreach (self::MINIMUM_SALDO_BY_BANK as $bank => $minimumSaldoConfig) {
            $messages[] = ' - ' . $minimumSaldoConfig['label'] . ' : '
                . $this->formatCurrency(
                    $this->getSaldoSaatIni($bulan, $bank),
                    $minimumSaldoConfig['label_currency']
                );
        }

        return implode("\n", $messages);
    }

    private function formatCurrency(float|int $nominal, string $labelCurrency): string
    {
        return $labelCurrency . ' ' . number_format($nominal, 0, ',', '.');
    }
}
