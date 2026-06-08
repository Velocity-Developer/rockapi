<?php

namespace App\Console\Commands;

use App\Models\ReminderCS;
use App\Models\User;
use App\Services\TelegramServices;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class KirimNotifReminderCSCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder-cs:kirim-notif-telegram
        {--jam= : Jam reminder yang dicek, format H:i. Default jam sekarang}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi Telegram reminder CS ke user role customer_service dan manager_advertising sesuai jam reminder.';

    /**
     * Execute the console command.
     */
    public function handle(TelegramServices $telegramServices)
    {
        $jam = $this->formatJam($this->option('jam') ?: Carbon::now()->format('H:i'));

        if (! $jam) {
            $this->error('Format jam tidak valid. Gunakan format H:i, contoh 18:00.');

            return self::FAILURE;
        }

        $reminders = ReminderCS::where('jam', $jam)->get();

        if ($reminders->isEmpty()) {
            $this->info('Tidak ada reminder CS untuk jam ' . $jam . '.');

            return self::SUCCESS;
        }

        $allReminders = ReminderCS::orderBy('jam')
            ->orderBy('id')
            ->get();

        // $users = User::role(['customer_service', 'manager_advertising'])
        $users = User::role(['manager_advertising'])
            ->whereNotNull('telegram_id')
            ->where('telegram_id', '!=', '')
            ->get();

        if ($users->isEmpty()) {
            $this->warn('Tidak ada user penerima dengan role customer_service atau manager_advertising yang memiliki telegram_id.');

            return self::SUCCESS;
        }

        $message = $this->buildMessage($allReminders, $jam);
        $sentCount = 0;

        foreach ($users as $user) {
            $telegramServices->sendMessage($user->telegram_id, $message);
            $sentCount++;

            Log::info('Reminder CS Telegram sent.', [
                'reminder_ids' => $reminders->pluck('id')->all(),
                'recipient_user_id' => $user->id,
                'jam' => $jam,
            ]);
        }

        $this->info($sentCount . ' notifikasi reminder CS berhasil dikirim untuk jam ' . $jam . '.');

        return self::SUCCESS;
    }

    private function buildMessage($reminders, string $jam): string
    {
        $message = "Reminder CS - {$jam}";
        $message .= "\n\nDaftar Reminder CS:";

        foreach ($reminders as $reminder) {
            $keterangan = $reminder->keterangan ?: '-';
            $message .= "\n" . $reminder->jam . ' - ' . $keterangan;
        }

        return $message;
    }

    private function formatJam(?string $jam): ?string
    {
        if (! $jam) {
            return null;
        }

        $jam = str_replace('.', ':', trim($jam));

        if (preg_match('/^(\d{1,2})(?::(\d{1,2}))?(?::\d{1,2})?$/', $jam, $matches)) {
            $hour = (int) $matches[1];
            $minute = isset($matches[2]) ? (int) $matches[2] : 0;

            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                return sprintf('%02d:%02d', $hour, $minute);
            }
        }

        return null;
    }
}
