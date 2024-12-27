<?php

namespace App\Data\Telegram\Keyboard;

use Spatie\LaravelData\Data;

class InlineKeyboardButtonData extends Data
{
    public function __construct(
        public readonly string $text,
        public readonly ?string $callback_data = null,
        public readonly ?string $url = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'text' => $this->text,
            'callback_data' => $this->callback_data,
            'url' => $this->url,
        ], fn($value) => $value !== null);
    }
}
