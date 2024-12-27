<?php

namespace App\Data\Telegram;

use Spatie\LaravelData\Data;

class CallbackQueryData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly UserData $from,
        public readonly MessageData $message,
        public readonly string $data,
    ) {}
}
