<?php

namespace App\Helpers;

class TelegramBotService
{
    private \Telegram $provider;
    private int $chat_id;
    private string $text;
    private int $message_id;


    public function __construct($use_proxy = true)
    {
        $proxy = $use_proxy ? ['url' => env('PROXY_URL'), 'port' => env('PROXY_PORT')] : [];

        $this->provider = new \Telegram(env('TELEGRAM_BOT_TOKEN'), true, $proxy);

        $this->chat_id = $this->provider->ChatID();
        $this->text = $this->provider->Text();
        $this->message_id = $this->provider->MessageID();
    }

    public function sendMessage(string|int $text, ?array $keyboard_structure = null, ?int $chat_id = null, ?string $keyboard_type = 'inline'): mixed
    {
        return $this->provider->sendMessage([
            'text' => $text,
            'chat_id' => $chat_id ?? $this->chat_id,
            'reply_markup' => $keyboard_structure ? $this->buildKeyboard($keyboard_structure, $keyboard_type) : null
        ]);
    }

    public function editMessage(string|int $text, ?array $keyboard_structure = null, mixed $message_to_edit = null, ?int $chat_id = null, ?string $keyboard_type = 'inline'): mixed
    {
        $message_id = $this->message_id;
        if (is_array($message_to_edit)) {
            $message_id = $message_to_edit['result']['message_id'];
        }
        if (is_numeric($message_to_edit)) {
            $message_id = $message_to_edit;
        }

        return $this->provider->editMessageText([
            'text' => $text,
            'chat_id' => $chat_id ?? $this->chat_id,
            'message_id' => $message_id,
            'reply_markup' => $keyboard_structure ? $this->buildKeyboard($keyboard_structure, $keyboard_type) : null
        ]);
    }

    public function editOrSendNewMessage(string|int $text, ?array $keyboard_structure = null, ?int $chat_id = null, ?string $keyboard_type = 'inline'): mixed
    {
        if ($this->isReplying()) {
            return $this->editMessage($text, $keyboard_structure, chat_id: $chat_id, keyboard_type: $keyboard_type);
        }

        return $this->sendMessage($text, $keyboard_structure, chat_id: $chat_id, keyboard_type: $keyboard_type);
    }

    public function buildKeyboard(array $structure, string $keyboard_type = 'inline'): string
    {
        $keyboard = [];
        $keyboardButtonBuilder = ($keyboard_type === 'inline') ? 'buildInlineKeyboardButton' : 'buildKeyboardButton';
        $keyboardBuilder = ($keyboard_type === 'inline') ? 'buildInlineKeyBoard' : 'buildKeyBoard';

        foreach ($structure as $keyboardRow) {
            $row = [];
            foreach ($keyboardRow as $title => $action) {
                $row[] = $this->provider->$keyboardButtonBuilder($title, '', $action);
            }
            $keyboard[] = $row;
        }

        return $this->provider->$keyboardBuilder($keyboard);
    }

    public function text(): string
    {
        return $this->text;
    }

    public function chatId()
    {
        return $this->chat_id;
    }

    private function isReplying(): bool
    {
        return $this->provider->getUpdateType() === $this->provider::CALLBACK_QUERY;
    }
}