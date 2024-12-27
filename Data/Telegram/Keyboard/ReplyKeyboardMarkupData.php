<?php

namespace App\Data\Telegram\Keyboard;

use Spatie\LaravelData\Data;

class ReplyKeyboardMarkupData extends Data
{
    public function __construct(
        /** @var array<array<KeyboardButtonData>> */
        public readonly array $keyboard,
        public readonly ?bool $resize_keyboard = true,
        public readonly ?bool $one_time_keyboard = false,
        public readonly ?bool $remove_keyboard = false,
    ) {}
}
