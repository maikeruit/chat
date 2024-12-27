<?php

namespace App\Data\Telegram;

use Spatie\LaravelData\Data;

class ContactData extends Data
{
    public function __construct(
        public readonly string $phone_number,
        public readonly string $first_name,
        public readonly ?string $last_name = null,
        public readonly ?int $user_id = null,
    ) {}
}
