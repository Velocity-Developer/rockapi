<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Invoice;

class TelegramController extends Controller
{
    //webhook get chat id
    public function webhook(Request $request)
    {
        $message = $request->input('message');
        if (!$message) {
            return response()->json(['ok' => false, 'message' => 'no message']);
        }

        $telegramId = $message['chat']['id'] ?? null;

        $text = trim($message['text'] ?? '');

        if (!$telegramId) {
            return response()->json(['ok' => false, 'message' => 'chat id not found']);
        }

        // Kalau user belum kirim apa-apa, kirim pesan welcome
        if ($text === '' || $text === '/start') {
            $this->sendMessage($telegramId, "👋 Selamat datang di *Nglorok Bot!*\n\nKirim kode Token Telegram yang kamu dapat dari dashboard untuk menghubungkan akun.", true);
            return response()->json(['ok' => true]);
        }

        //kalau text ada 'TelegramToken_' di awal, itu token verifikasi
        if (strpos($text, 'TelegramToken_') === 0) {

            //ambil token dari text
            $userId = intval(cache()->get($text));

            if (!$userId) {
                $this->sendMessage($telegramId, "❌ Kode verifikasi tidak valid atau sudah kadaluarsa.");
                return response()->json(['ok' => false, 'message' => 'invalid token']);
            }

            // Cek user
            $user = User::find($userId);
            if (!$user) {
                $this->sendMessage($telegramId, "❌ User tidak ditemukan di sistem.");
                return response()->json(['ok' => false, 'message' => 'user not found']);
            }

            // Simpan telegram_id ke user
            $user->telegram_id = $telegramId;
            $user->save();

            // Hapus token biar tidak bisa dipakai ulang
            cache()->forget($text);

            // Kirim pesan sukses
            $this->sendMessage($telegramId, "✅ Verifikasi berhasil!\nAkun kamu sudah terhubung dengan bot Telegram ini.");

            return response()->json(['ok' => true]);
        }

        //kalau text ada 'invoice=' di awal, itu invoice
        if (strpos($text, 'invoice=') === 0) {
            $msg = $this->checkInvoice($text);
            $this->sendMessage($telegramId, $msg);
        }

        return response()->json(['ok' => true]);
    }

    //status notifikasi user login
    public function status(Request $request)
    {
        $user = auth()->user();
        $telegramId = $user->telegram_id;
        $telegramToken = '';

        $webhook_url = '';
        if (!$telegramId) {

            //create cache token for 12 hour
            $telegramToken = 'TelegramToken_' . uniqid();
            cache()->put($telegramToken, $user->id, 60 * 60 * 12);

            $webhook_url = 'https://t.me/NewVDnetbot?start=' . $telegramToken;
        }

        return response()->json([
            'telegram_id' => $telegramId,
            'webhook_url' => $webhook_url,
            'telegram_token' => $telegramToken,
        ]);
    }

    private function sendMessage($chatId, $text, $parseMarkdown = false)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($parseMarkdown) {
            $data['parse_mode'] = 'Markdown';
        }

        try {
            $client = new \GuzzleHttp\Client();
            $client->post($url, ['form_params' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message: ' . $e->getMessage());
        }
    }

    private function checkInvoice($text)
    {
        //pecah text invoice_
        $invoiceId = str_replace('invoice=', '', $text);
        $invoice = Invoice::where('nomor', $invoiceId)->first();
        if (!$invoice) {
            return "❌ Invoice tidak ditemukan di sistem.";
        }

        //return invoice
        return "✅ Invoice ditemukan:\n\nNomor Invoice: {$invoice->nomor}\nTotal: Rp. {$invoice->total}\nStatus: {$invoice->status}";
    }
}
