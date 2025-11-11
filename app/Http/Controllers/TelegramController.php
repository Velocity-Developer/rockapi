<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TelegramController extends Controller
{
    //webhook get chat id
    public function webhook(Request $request)
    {
        $message = $request->input('message');
        if (!$message) return response()->json(['ok' => false]);

        $telegramId = $message['chat']['id'];

        //simpan ke user login
        $user = auth()->user();
        $user->telegram_id = $telegramId;
        $user->save();

        return response()->json(['ok' => true]);
    }

    //status notifikasi user login
    public function status(Request $request)
    {
        $user = auth()->user();
        $telegramId = $user->telegram_id;

        $webhook_url = '';
        if (!$telegramId) {
            $TELEGRAM_BOT_TOKEN = env('TELEGRAM_BOT_TOKEN', 'XXX');
            $webhook_url = 'https://api.telegram.org/bot' . $TELEGRAM_BOT_TOKEN . '/setWebhook?url=' . config('app.url') . '/api/telegram/webhook';
        }

        return response()->json([
            'telegram_id' => $telegramId,
            'webhook_url' => $webhook_url,
        ]);
    }
}
