<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramChatMessage extends Model
{
    protected $fillable = [
        'update_id',
        'telegram_message_id',
        'telegram_user_id',
        'chat_id',
        'text',
        'entities',
        'attachments',
        'direction',
        'type',
        'operator_id',
        'reply_to_message_id',
        'is_edited',
        'telegram_timestamp',
        'bot_id',
    ];

    protected $casts = [
        'entities' => 'array',
        'attachments' => 'array',
        'is_edited' => 'boolean',
        'telegram_timestamp' => 'datetime',
    ];

    /**
     * The attributes that should be cast to enum types.
     *
     * @var array<string, string>
     */
    protected $enums = [
        'direction' => ['incoming', 'outgoing'],
        'type' => [
            'text',
            'photo',
            'video',
            'document',
            'audio',
            'voice',
            'location',
            'contact',
            'sticker',
            'callback_query',
        ],
    ];

    /**
     * Get the telegram user that owns the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id', 'user_id');
    }

    /**
     * Get the operator that processed the message.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Get the message this message is replying to.
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(TelegramChatMessage::class, 'reply_to_message_id', 'telegram_message_id');
    }

    /**
     * Check if the message is incoming.
     */
    public function isIncoming(): bool
    {
        return $this->direction === 'incoming';
    }

    /**
     * Check if the message is outgoing.
     */
    public function isOutgoing(): bool
    {
        return $this->direction === 'outgoing';
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'bot_id');
    }
} 