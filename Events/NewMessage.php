<?php

namespace App\Events;

use App\Models\TelegramChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TelegramChatMessage $message)
    {
        $this->message->load(['user', 'operator']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('chat.' . $this->message->bot_id . '.' . $this->message->chat_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NewMessage';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'text' => $this->message->text,
                'direction' => $this->message->direction,
                'telegram_timestamp' => $this->message->telegram_timestamp,
                'type' => $this->message->type,
                'entities' => $this->message?->entities,
                'attachments' => $this->message?->attachments,
                'operator' => $this->message->operator ? [
                    'id' => $this->message->operator->id,
                    'name' => $this->message->operator->name,
                ] : null,
                'user' => $this->message->user ? [
                    'id' => $this->message->user->user_id,
                    'first_name' => $this->message->user->first_name,
                    'last_name' => $this->message->user->last_name,
                    'username' => $this->message->user->username,
                ] : [
                    'id' => $this->message->chat_id,
                    'first_name' => 'User',
                    'last_name' => '',
                    'username' => null,
                ],
            ]
        ];
    }
}
