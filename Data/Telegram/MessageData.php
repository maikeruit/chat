<?php

namespace App\Data\Telegram;

use Spatie\LaravelData\Data;

class MessageData extends Data
{
    public function __construct(
        public readonly int $message_id,
        public readonly UserData $from,
        public readonly ChatData $chat,
        public readonly int $date,
        public readonly ?string $text = null,
        public readonly ?array $entities = null,
        public readonly ?array $photo = null,
        public readonly ?object $video = null,
        public readonly ?object $audio = null,
        public readonly ?object $document = null,
        public readonly ?object $voice = null,
        public readonly ?object $sticker = null,
        public readonly ?object $location = null,
        public readonly ?ContactData $contact = null,
        public readonly ?string $caption = null,
        public readonly ?array $caption_entities = null,
        public readonly ?MessageData $reply_to_message = null,
        public readonly ?bool $edit_date = null,
    ) {}

    public function isCommand(): bool
    {
        return $this->entities !== null
            && count($this->entities) > 0
            && $this->entities[0]['type'] === 'bot_command';
    }

    public function getCommand(): ?string
    {
        if (!$this->isCommand()) {
            return null;
        }

        // Извлекаем команду из текста, убирая возможные параметры
        $command = explode(' ', $this->text)[0];
        // Убираем @ если команда содержит упоминание бота
        return explode('@', $command)[0];
    }

    public function hasContact(): bool
    {
        return $this->contact !== null;
    }

    public function hasPhoto(): bool
    {
        return $this->photo !== null;
    }

    public function hasVideo(): bool
    {
        return $this->video !== null;
    }

    public function hasAudio(): bool
    {
        return $this->audio !== null;
    }

    public function hasDocument(): bool
    {
        return $this->document !== null;
    }

    public function hasVoice(): bool
    {
        return $this->voice !== null;
    }

    public function hasSticker(): bool
    {
        return $this->sticker !== null;
    }

    public function hasLocation(): bool
    {
        return $this->location !== null;
    }

    public function hasCaption(): bool
    {
        return $this->caption !== null;
    }

    public function isEdited(): bool
    {
        return $this->edit_date !== null;
    }

    public function hasReplyTo(): bool
    {
        return $this->reply_to_message !== null;
    }
}
