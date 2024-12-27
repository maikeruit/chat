<?php

namespace App\Data\Telegram\Requests;

use App\Data\Telegram\Keyboard\InlineKeyboardMarkupData;
use App\Data\Telegram\Keyboard\ReplyKeyboardMarkupData;
use Spatie\LaravelData\Data;

class SendMessageData extends Data
{
    public function __construct(
        public readonly int|string $chat_id,
        public readonly string $text,
        public readonly ?string $parse_mode = null,
        public readonly ?bool $disable_web_page_preview = null,
        public readonly ?bool $disable_notification = null,
        public readonly ?int $reply_to_message_id = null,
        public readonly null|InlineKeyboardMarkupData|ReplyKeyboardMarkupData $reply_markup = null,
    ) {}

    public function toArray(): array
    {
        $data = [
            'chat_id' => $this->chat_id,
            'text' => $this->text,
            'parse_mode' => $this->parse_mode,
            'disable_web_page_preview' => $this->disable_web_page_preview,
            'disable_notification' => $this->disable_notification,
            'reply_to_message_id' => $this->reply_to_message_id,
        ];

        if ($this->reply_markup !== null) {
            $data['reply_markup'] = $this->reply_markup->toArray();
        }

        return array_filter($data, fn($value) => $value !== null);
    }
}
