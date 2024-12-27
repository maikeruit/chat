<?php

namespace App\Data\Telegram;

use Spatie\LaravelData\Data;

class ChatData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly ?string $title = null,
        public readonly ?string $username = null,
        public readonly ?string $first_name = null,
        public readonly ?string $last_name = null,
    ) {}
}
