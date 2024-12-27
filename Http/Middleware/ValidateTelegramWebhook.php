<?php

namespace App\Http\Middleware;

use App\Models\TelegramBot;
use Closure;
use Illuminate\Http\Request;

class ValidateTelegramWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
        $urlSecret = $request->route('secret');
        
        // Находим бота по secret_token из URL
        $bot = TelegramBot::where('secret_token', $urlSecret)->first();
        
        if (!$bot || !$secretToken || $secretToken !== $urlSecret) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
} 