<?php

namespace App\Data\Telegram;

use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public readonly ?int $update_id,
        public readonly ?MessageData $message = null,
        public readonly ?MessageData $edited_message = null,
        public readonly ?MessageData $channel_post = null,
        public readonly ?MessageData $edited_channel_post = null,
        public readonly ?CallbackQueryData $callback_query = null,
    ) {}
}
