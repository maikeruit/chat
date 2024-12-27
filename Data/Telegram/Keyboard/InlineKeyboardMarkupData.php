<?php

namespace App\Data\Telegram\Keyboard;

use Spatie\LaravelData\Data;

class InlineKeyboardMarkupData extends Data
{
    public function __construct(
        /** @var array<array<InlineKeyboardButtonData>> */
        public readonly array $inline_keyboard,
    ) {}

    public function toArray(): array
    {
        return [
            'inline_keyboard' => array_map(
                fn(array $row) => array_map(
                    fn(InlineKeyboardButtonData $button) => $button->toArray(),
                    $row
                ),
                $this->inline_keyboard
            )
        ];
    }
}
