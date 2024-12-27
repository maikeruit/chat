<?php

namespace App\Services;

use App\Models\TelegramBot;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class TelegramBotManagerService
{
    public function setWebhook(TelegramBot $bot, string $url, string $secretToken): Response
    {
        return Http::post(
            "https://api.telegram.org/bot{$bot->token}/setWebhook",
            [
                'url' => $url,
                'secret_token' => $secretToken,
            ]
        );
    }

    public function getWebhookInfo(TelegramBot $bot): Response
    {
        return Http::get("https://api.telegram.org/bot{$bot->token}/getWebhookInfo");
    }

    public function getBotInfo(string $token): Response
    {
        return Http::get("https://api.telegram.org/bot{$token}/getMe");
    }

    public function validateSecretToken(string $secretToken): ?TelegramBot
    {
        return TelegramBot::where('secret_token', $secretToken)
            ->where('is_active', true)
            ->first();
    }

    public function deleteWebhook(TelegramBot $bot): Response
    {
        return Http::post(
            "https://api.telegram.org/bot{$bot->token}/deleteWebhook"
        );
    }

    public function activateWebhook(TelegramBot $bot): Response
    {
        $baseUrl = $bot->webhook_url ?: config('app.url');
        $webhookPath = route('telegram.webhook', ['secret' => $bot->secret_token], false);
        $fullWebhookUrl = rtrim($baseUrl, '/') . $webhookPath;

        return $this->setWebhook($bot, $fullWebhookUrl, $bot->secret_token);
    }
} 