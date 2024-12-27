<?php

namespace App\Services;

use App\Data\Telegram\Requests\SendMessageData;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class TelegramBotService
{
    private string $baseUrl;
    private ?User $user;

    /**
     *
     */
    public function __construct()
    {
        $this->user = auth()->user();
    }

    public function setToken(string $token): void
    {
        $this->baseUrl = "https://api.telegram.org/bot{$token}";
    }

    public function sendMessage(SendMessageData $message): Response
    {
        Log::debug('Message', $message->toArray());

        return Http::post(
            "{$this->baseUrl}/sendMessage",
            $message->toArray()
        );
    }

    public function sendMarkdownMessage(int|string $chatId, string $text): Response
    {
        return $this->sendMessage(new SendMessageData(
            chat_id: $chatId,
            text: $text,
            parse_mode: 'MarkdownV2'
        ));
    }

    public function sendHtmlMessage(int|string $chatId, string $text): Response
    {
        return $this->sendMessage(new SendMessageData(
            chat_id: $chatId,
            text: $text,
            parse_mode: 'HTML'
        ));
    }

    public function getCurrentUser(): ?User
    {
        return $this->user;
    }
}
