<?php

namespace App\Services;

use App\Data\Telegram\CallbackQueryData;
use App\Data\Telegram\MessageData;
use App\Data\Telegram\UpdateData;
use App\Models\TelegramChatMessage;

class TelegramMessageService
{
    private ?int $botId = null;

    public function setBotId(int $botId): void
    {
        $this->botId = $botId;
    }

    public function storeUpdate(UpdateData $update): ?TelegramChatMessage
    {
        if ($update->message !== null) {
            return $this->storeMessage($update->message, $update->update_id);
        }

        if ($update->edited_message !== null) {
            return $this->storeMessage($update->edited_message, $update->update_id,true);
        }

        if ($update->callback_query !== null) {
            return $this->storeCallbackQuery($update->update_id, $update->callback_query);
        }

        return null;
    }

    public function storeOutgoingMessage(UpdateData $update, int $operatorId): ?TelegramChatMessage
    {
        if (!$this->botId) {
            throw new \RuntimeException('Bot ID is not set');
        }

        if ($update->message) {
            return TelegramChatMessage::create([
                'update_id' => $update->update_id,
                'telegram_message_id' => $update->message->message_id,
                'telegram_user_id' => null,
                'chat_id' => $update->message->chat->id,
                'text' => $update->message->text,
                'entities' => $update->message->entities,
                'direction' => 'outgoing',
                'type' => 'text',
                'operator_id' => $operatorId,
                'telegram_timestamp' => now(),
                'bot_id' => $this->botId,
            ]);
        }

        return null;
    }

    protected function storeMessage(MessageData $message, ?int $updateId = null, bool $isEdited = false): TelegramChatMessage
    {
        if (!$this->botId) {
            throw new \RuntimeException('Bot ID is not set');
        }

        $data = [
            'update_id' => $updateId,
            'telegram_message_id' => $message->message_id,
            'telegram_user_id' => $message->from->id,
            'chat_id' => $message->chat->id,
            'direction' => 'incoming',
            'is_edited' => $isEdited,
            'telegram_timestamp' => $message->date,
            'bot_id' => $this->botId,
        ];

        if ($message->text !== null) {
            $data['type'] = 'text';
            $data['text'] = $message->text;
            $data['entities'] = $message->entities;
        } elseif ($message->hasPhoto()) {
            $data['type'] = 'photo';
            $data['attachments'] = [
                'photo_sizes' => $message->photo,
                'caption' => $message->caption,
                'caption_entities' => $message->caption_entities,
            ];
            $data['text'] = $message->caption;
        } elseif ($message->hasVideo()) {
            $data['type'] = 'video';
            $data['attachments'] = [
                'video' => $message->video,
                'caption' => $message->caption,
                'caption_entities' => $message->caption_entities,
            ];
            $data['text'] = $message->caption;
        } elseif ($message->hasDocument()) {
            $data['type'] = 'document';
            $data['attachments'] = [
                'document' => $message->document,
                'caption' => $message->caption,
                'caption_entities' => $message->caption_entities,
            ];
            $data['text'] = $message->caption;
        } elseif ($message->hasVoice()) {
            $data['type'] = 'voice';
            $data['attachments'] = [
                'voice' => $message->voice,
            ];
        } elseif ($message->hasAudio()) {
            $data['type'] = 'audio';
            $data['attachments'] = [
                'audio' => $message->audio,
                'caption' => $message->caption,
                'caption_entities' => $message->caption_entities,
            ];
            $data['text'] = $message->caption;
        } elseif ($message->hasSticker()) {
            $data['type'] = 'sticker';
            $data['attachments'] = [
                'sticker' => $message->sticker,
            ];
        } elseif ($message->hasLocation()) {
            $data['type'] = 'location';
            $data['attachments'] = [
                'location' => $message->location,
            ];
        } elseif ($message->hasContact()) {
            $data['type'] = 'contact';
            $data['attachments'] = [
                'contact' => $message->contact,
            ];
            $data['text'] = json_encode($message->contact);
        }

        if ($message->hasReplyTo()) {
            $data['reply_to_message_id'] = $message->reply_to_message->message_id;
        }

        return TelegramChatMessage::create($data);
    }

    protected function storeCallbackQuery(int $updateId, CallbackQueryData $callback): TelegramChatMessage
    {
        if (!$this->botId) {
            throw new \RuntimeException('Bot ID is not set');
        }

        return TelegramChatMessage::create([
            'update_id' => $updateId,
            'telegram_message_id' => $callback->message->message_id,
            'telegram_user_id' => $callback->from->id,
            'chat_id' => $callback->message->chat->id,
            'text' => $callback->data,
            'direction' => 'incoming',
            'type' => 'callback_query',
            'telegram_timestamp' => now(),
            'bot_id' => $this->botId,
        ]);
    }
}
