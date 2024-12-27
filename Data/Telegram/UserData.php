<?php

namespace App\Data\Telegram;

use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly bool $is_bot,
        public readonly string $first_name,
        public readonly ?string $last_name = null,
        public readonly ?string $username = null,
        public readonly ?string $language_code = null,
    ) {}
}
