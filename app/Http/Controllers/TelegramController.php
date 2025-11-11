<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class TelegramController extends Controller
{
    //webhook get chat id
    public function webhook(Request $request)
    {
        $message = $request->input('message');
        if (!$message) return response()->json(['ok' => false, 'message' => 'message not found']);

        $telegramId = $message['chat']['id'];

        //check token
        $token = $request->input('token');
        if (!$token) return response()->json(['ok' => false, 'message' => 'token not found']);

        //get user id from cache
        $userId = cache()->get($token);
        if (!$userId) return response()->json(['ok' => false, 'message' => 'token not valid']);

        //get user from db
        $user = User::find($userId);
        if (!$user) return response()->json(['ok' => false, 'message' => 'user not found']);

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

            //create cache token for 1 hour
            $token = 'telegram_token_' . uniqid();
            cache()->put($token, $user->id, 60 * 60);

            $TELEGRAM_BOT_TOKEN = env('TELEGRAM_BOT_TOKEN', 'XXX');
            $webhook_url = 'https://api.telegram.org/bot' . $TELEGRAM_BOT_TOKEN . '/setWebhook?url=' . config('app.url') . '/api/telegram/webhook?token=' . $token;
        }

        return response()->json([
            'telegram_id' => $telegramId,
            'webhook_url' => $webhook_url,
        ]);
    }
}
