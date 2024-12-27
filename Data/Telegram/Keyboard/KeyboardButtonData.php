<?php

namespace App\Data\Telegram\Keyboard;

use Spatie\LaravelData\Data;

class KeyboardButtonData extends Data
{
    public function __construct(
        public readonly string $text,
        public readonly ?bool $request_contact = null,
        public readonly ?bool $request_location = null,
    ) {}
}
