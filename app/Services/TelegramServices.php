<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TelegramServices
{
    /**
     * Create a new class instance.
     */
    public function sendMessage($chatId, $text, $parseMarkdown = false)
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
            $client = new \GuzzleHttp\Client;
            $client->post($url, ['form_params' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message: ' . $e->getMessage());
        }
    }
}
