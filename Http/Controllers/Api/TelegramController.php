<?php

namespace App\Http\Controllers\Api;

use App\Data\Telegram\CallbackQueryData;
use App\Data\Telegram\Keyboard\InlineKeyboardButtonData;
use App\Data\Telegram\Keyboard\InlineKeyboardMarkupData;
use App\Data\Telegram\MessageData;
use App\Data\Telegram\Requests\SendMessageData;
use App\Data\Telegram\UpdateData;
use App\Data\Telegram\UserData;
use App\Http\Controllers\Controller;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use App\Services\Telegram\KeyboardFactory;
use App\Services\TelegramBotService;
use App\Services\TelegramMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\TelegramBot;
use App\Events\NewMessage;

class TelegramController extends Controller
{
    public function __construct(private readonly TelegramBotService $telegram, private readonly TelegramMessageService $messageService)
    {

    }

    public function webhook(Request $request, string $secret)
    {
        Log::debug('Debug');
        // Находим бота по secret_token
        $bot = TelegramBot::where('secret_token', $secret)->firstOrFail();

        // Инициализируем сервис с токеном конкретного бота
        $this->telegram->setToken($bot->token);

        $update = UpdateData::from($request->all());

        Log::debug('Request', $request->all());

        if ($update->message) {
            // Создаем или обновляем пользователя при любом сообщении
            $this->createOrUpdateUser($update->message->from);

            // Проверяем согласие перед выполнением команд
            if (!$this->checkUserAgreement($update->message->from->id)) {
                // Отправляем сообщение с запросом согласия
                $keyboard = new InlineKeyboardMarkupData(inline_keyboard: [[
                    new InlineKeyboardButtonData(
                        text: 'Согласен ✅',
                        callback_data: 'accept_agreement'
                    )
                ]]);

                $text = "Добро пожаловать, {$update->message->from->first_name}!\n\n"
                    . "Перед началом использования бота, пожалуйста, ознакомьтесь с условиями:\n\n"
                    . "1. Я даю согласие на обработку моих персональных данных\n"
                    . "2. Я принимаю условия пользовательского соглашения\n"
                    . "3. Я соглашаюсь получать уведомления от бота\n\n"
                    . "Для продолжения работы необходимо принять условия.";

                $this->telegram->sendMessage(new SendMessageData(
                    chat_id: $update->message->chat->id,
                    text: $text,
                    reply_markup: $keyboard
                ));

                return;
            }

            if ($update->message?->isCommand()) {
                match ($update->message->getCommand()) {
                    '/start' => $this->handleStartCommand($update->message),
                    default => $this->handleUnknownCommand($update->message),
                };

                return;
            }


            // Only process messages if user has agreed
            if ($this->checkUserAgreement($update->message->from->id)) {
                // Get current registration step
                $currentMessage = TelegramMessage::where('telegram_user_id', $update->message->from->id)
                    ->whereNotIn('registration_step', ['completed'])
                    ->latest()
                    ->first();

                if ($currentMessage) {
                    switch ($currentMessage->registration_step) {
                        case 'phone':
                            if ($update->message->hasContact()) {
                                $currentMessage->update([
                                    'phone_number' => $update->message->contact->phone_number,
                                    'registration_step' => 'address'
                                ]);

                                $this->telegram->sendMessage(new SendMessageData(
                                    chat_id: $update->message->chat->id,
                                    text: "Спасибо! Теперь укажите адрес объекта:",
                                ));
                            }
                            break;

                        case 'address':
                            $currentMessage->update([
                                'address' => $update->message->text,
                                'registration_step' => 'area'
                            ]);

                            $this->telegram->sendMessage(new SendMessageData(
                                chat_id: $update->message->chat->id,
                                text: "Укажите площадь помещения (в квадратных метрах):"
                            ));
                            break;

                        case 'area':
                            if (is_numeric($update->message->text)) {
                                $currentMessage->update([
                                    'area' => (int)$update->message->text,
                                    'registration_step' => 'additional_info'
                                ]);

                                $this->telegram->sendMessage(new SendMessageData(
                                    chat_id: $update->message->chat->id,
                                    text: "Укажите дополнительную информацию или особые пожелания:"
                                ));
                            } else {
                                $this->telegram->sendMessage(new SendMessageData(
                                    chat_id: $update->message->chat->id,
                                    text: "Пожалуйста, введите числовое значение площади."
                                ));
                            }
                            break;

                        case 'additional_info':
                            $currentMessage->update([
                                'additional_info' => $update->message->text,
                                'registration_step' => 'completed',
                                'status' => 'new'
                            ]);

                            $this->telegram->sendMessage(new SendMessageData(
                                chat_id: $update->message->chat->id,
                                text: "Спасибо! Ваша заявка принята. Мы свяжемся с вами в ближайшее время."
                            ));
                            break;
                    }
                }
            }
        } else if ($update->callback_query) {
            $this->handleCallback($update->callback_query);
        }

        $this->messageService->setBotId($bot->id);
        $message = $this->messageService->storeUpdate($update);

        Log::debug('Update', [
            'update_id' => $update->update_id,
            'message' => $update->message?->text,
            'from' => $update->message?->from->username,
        ]);

        broadcast(new NewMessage($message));
        return response()->json(['status' => 'ok']);
    }

    protected function handleStartCommand(MessageData $message): void
    {
        $keyboard = new InlineKeyboardMarkupData(inline_keyboard: [[
            new InlineKeyboardButtonData(
                text: 'Создать заявку',
                callback_data: 'create_request'
            )
        ]]);

        $this->telegram->sendMessage(new SendMessageData(
            chat_id: $message->chat->id,
            text: "Здравствуйте. Теперь вы можете создать заявку.",
            parse_mode: 'HTML',
            reply_markup: $keyboard
        ));
    }

    protected function handleUnknownCommand(MessageData $message)
    {
        $this->telegram->sendHtmlMessage(
            $message->chat->id,
            "Неизвестная команда!"
        );
    }

    protected function handleCallback(CallbackQueryData $callback): void
    {
        if ($callback->data === 'accept_agreement') {
            // Получаем или создаем запись пользователя
            if (!$this->checkUserAgreement($callback->from->id)) {
                $telegramUser = TelegramUser::updateOrCreate(
                    ['user_id' => $callback->from->id],
                    [
                        'first_name' => $callback->from->first_name,
                        'last_name' => $callback->from->last_name,
                        'username' => $callback->from->username,
                        'is_agreed' => true
                    ]
                );

                $keyboard = new InlineKeyboardMarkupData(inline_keyboard: [[
                    new InlineKeyboardButtonData(
                        text: 'Создать заявку',
                        callback_data: 'create_request'
                    )
                ]]);

                $this->telegram->sendMessage(new SendMessageData(
                    chat_id: $callback->message->chat->id,
                    text: "Спасибо! Ваше согласие получено. Теперь вы можете пользоваться ботом в полном объеме.",
                    parse_mode: 'HTML',
                    reply_markup: $keyboard
                ));
            }
        }

        if ($callback->data === 'create_request') {
            // Create new message record
            $message = TelegramMessage::create([
                'telegram_user_id' => $callback->from->id,
                'registration_step' => 'phone'
            ]);

            // Request phone number using keyboard
            $keyboard = KeyboardFactory::makeContactRequestKeyboard('Поделиться номером телефона');

            $this->telegram->sendMessage(new SendMessageData(
                chat_id: $callback->message->chat->id,
                text: "Для создания заявки, пожалуйста, поделитесь своим номером телефона.",
                reply_markup: $keyboard
            ));
        }
    }

    protected function checkUserAgreement(int $userId): bool
    {
        return TelegramUser::where('user_id', $userId)
            ->where('is_agreed', true)
            ->exists();
    }

    protected function createOrUpdateUser(UserData $userData): void
    {
        TelegramUser::updateOrCreate(
            ['user_id' => $userData->id],
            [
                'first_name' => $userData->first_name,
                'last_name' => $userData->last_name,
                'username' => $userData->username,
            ]
        );
    }
}
