<?php

namespace App\Services\Telegram;

use App\Data\Telegram\Keyboard\InlineKeyboardButtonData;
use App\Data\Telegram\Keyboard\InlineKeyboardMarkupData;
use App\Data\Telegram\Keyboard\KeyboardButtonData;
use App\Data\Telegram\Keyboard\ReplyKeyboardMarkupData;

class KeyboardFactory
{
    public static function makeSimpleKeyboard(array $buttons, int $columnsCount = 2): ReplyKeyboardMarkupData
    {
        $keyboard = array_chunk(
            array_map(
                fn(string $text) => new KeyboardButtonData(text: $text),
                $buttons
            ),
            $columnsCount
        );

        return new ReplyKeyboardMarkupData(
            keyboard: $keyboard,
            resize_keyboard: true
        );
    }

    public static function makeInlineKeyboard(array $buttons, int $columnsCount = 2): InlineKeyboardMarkupData
    {
        $keyboard = array_chunk(
            array_map(
                fn(array $button) => new InlineKeyboardButtonData(
                    text: $button['text'],
                    callback_data: $button['callback_data'] ?? $button['text']
                ),
                $buttons
            ),
            $columnsCount
        );

        return new InlineKeyboardMarkupData(inline_keyboard: $keyboard);
    }

    public static function makeReplyKeyboard(array $buttons, int $columnsCount = 2): ReplyKeyboardMarkupData
    {
        $keyboard = array_chunk(
            array_map(
                fn(string $text) => new KeyboardButtonData(text: $text),
                $buttons
            ),
            $columnsCount
        );

        return new ReplyKeyboardMarkupData(
            keyboard: $keyboard,
            resize_keyboard: true
        );
    }

    public static function makeContactRequestKeyboard(string $buttonText = 'Поделиться контактом'): ReplyKeyboardMarkupData
    {
        return new ReplyKeyboardMarkupData(
            keyboard: [[new KeyboardButtonData(
                text: $buttonText,
                request_contact: true
            )]],
            resize_keyboard: true,
            one_time_keyboard: true
        );
    }
}
