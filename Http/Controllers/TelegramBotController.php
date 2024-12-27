<?php

namespace App\Http\Controllers;

use App\Data\Telegram\Requests\SendMessageData;
use App\Data\Telegram\UpdateData;
use App\Events\NewMessage;
use App\Models\TelegramBot;
use App\Services\TelegramBotManagerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TelegramChatMessage;
use App\Services\TelegramBotService;
use App\Services\TelegramMessageService;

class TelegramBotController extends Controller
{
    public function __construct(
        private readonly TelegramBotManagerService $botManager,
        private readonly TelegramBotService $telegram,
        private readonly TelegramMessageService $messageService
    ) {}

    public function index()
    {
        return Inertia::render('TelegramBots/Index', [
            'bots' => auth()->user()->telegramBots
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'token' => 'required|string|unique:telegram_bots,token'
        ]);

        // Проверяем валидность токена
        $botInfo = $this->botManager->getBotInfo($validated['token']);
        if (!$botInfo->successful()) {
            return back()->withErrors(['token' => 'Invalid bot token']);
        }

        $bot = auth()->user()->telegramBots()->create([
            'name' => $validated['name'],
            'token' => $validated['token'],
            'username' => $botInfo->json('result.username'),
        ]);

        // Формируем полный URL для вебхука, используя secret_token
        $baseUrl = config('app.url');
        $webhookPath = route('telegram.webhook', ['secret' => $bot->secret_token], false);
        $fullWebhookUrl = "{$baseUrl}{$webhookPath}";

        // Устанавливаем вебхук, передавая secret_token и в заголовке
        $this->botManager->setWebhook($bot, $fullWebhookUrl, $bot->secret_token);

        return redirect()->route('telegram-bots.index');
    }

    public function updateWebhook(Request $request, TelegramBot $bot)
    {
        $validated = $request->validate([
            'webhook_url' => 'required|url'
        ]);

        // Сохраняем базовый URL
        $baseWebhookUrl = rtrim($validated['webhook_url'], '/');

        // Формируем полный URL для вебхука, используя secret_token
        $webhookPath = route('telegram.webhook', ['secret' => $bot->secret_token], false);
        $fullWebhookUrl = "{$baseWebhookUrl}{$webhookPath}";

        // Проверяем и устанавливаем вебхук, передавая secret_token и в заголовке
        $response = $this->botManager->setWebhook($bot, $fullWebhookUrl, $bot->secret_token);

        if (!$response->successful()) {
            return back()->withErrors(['webhook_url' => 'Failed to set webhook URL']);
        }

        // Сохраняем только базовый URL
        $bot->update([
            'webhook_url' => $baseWebhookUrl,
            'is_active' => true
        ]);

        return back()->with('success', 'Webhook URL updated successfully');
    }

    public function getWebhookInfo(TelegramBot $bot)
    {
        $response = $this->botManager->getWebhookInfo($bot);

        return response()->json($response->json());
    }

    public function showBots()
    {
        return Inertia::render('Chats/Index', [
            'bots' => auth()->user()->telegramBots
                ->map(fn($bot) => [
                    'id' => $bot->id,
                    'name' => $bot->name,
                    'username' => $bot->username,
                    'is_active' => $bot->is_active,
                ])
        ]);
    }

    public function showBotChats(TelegramBot $bot)
    {
        $chats = TelegramChatMessage::where('telegram_user_id', '!=', null)
            ->where('bot_id', $bot->id)
            ->select('chat_id', 'telegram_user_id')
            ->selectRaw('MAX(created_at) as last_message_at')
            ->selectRaw('COUNT(*) as messages_count')
            ->groupBy('chat_id', 'telegram_user_id')
            ->with('user')
            ->orderByDesc('last_message_at')
            ->get();

        return Inertia::render('Chats/Show', [
            'bot' => [
                'id' => $bot->id,
                'name' => $bot->name,
                'username' => $bot->username,
            ],
            'chats' => $chats
        ]);
    }

    public function showChat(TelegramBot $bot, $chatId)
    {
        $messages = TelegramChatMessage::where('chat_id', $chatId)
            ->where('bot_id', $bot->id)
            ->with(['user', 'operator'])
            ->orderBy('telegram_timestamp', 'asc')
            ->paginate(50);

        $chat = TelegramChatMessage::where('chat_id', $chatId)
            ->where('bot_id', $bot->id)
            ->with('user')
            ->first();

        return Inertia::render('Chats/Chat', [
            'bot' => [
                'id' => $bot->id,
                'name' => $bot->name,
                'username' => $bot->username,
            ],
            'chat' => [
                'id' => $chatId,
                'user' => $chat->user,
            ],
            'messages' => $messages
        ]);
    }

    public function sendMessage(Request $request, TelegramBot $bot, $chatId)
    {
        $validated = $request->validate([
            'text' => 'required|string'
        ]);

        // Отправляем сообщение через Telegram API
        $this->telegram->setToken($bot->token);
        $response = $this->telegram->sendMessage(new SendMessageData(
            chat_id: $chatId,
            text: $validated['text']
        ));

        if ($response->successful()) {
            // Преобразуем ответ в DTO
            $update = UpdateData::from([
                'message' => $response->json('result')
            ]);

            // Устанавливаем ID бота перед сохранением сообщения
            $this->messageService->setBotId($bot->id);

            // Сохраняем сообщение через сервис с указанием operator_id
            $message = $this->messageService->storeOutgoingMessage($update, auth()->id());

            broadcast(new NewMessage($message));
        } else {
            return back()->withErrors(['message' => 'Failed to send message']);
        }

        return back();
    }

    public function deleteWebhook(TelegramBot $bot)
    {
        $response = $this->botManager->deleteWebhook($bot);

        if (!$response->successful()) {
            return back()->withErrors(['webhook' => 'Failed to delete webhook']);
        }

        $bot->update([
            'webhook_url' => null,
            'is_active' => false
        ]);

        return back()->with('success', 'Webhook deleted successfully');
    }
}
