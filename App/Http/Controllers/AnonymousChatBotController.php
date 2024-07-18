<?php

namespace App\Http\Controllers;

use App\Helpers\TelegramBotService;
use App\Models\ChatMatch;
use App\Models\ChatRequest;
use App\Models\Command;
use App\Models\User;

class AnonymousChatBotController extends BaseController
{
    protected TelegramBotService $telegram;
    protected ?User $user = null;

    public function __construct()
    {
        parent::__construct();

        $this->telegram = new TelegramBotService();
        $this->user = $this->findOrCreateUser();
    }

    public function inbounce(): void
    {
        if ($this->telegram->text() === '/start') {
            $this->showStartMenu();
            return;
        }

        if ($this->telegram->text() === '/request') {
            $this->handleRequestForChat();
            return;
        }

        if ($this->telegram->text() === '/stop') {
            $this->stopOngoingChat();
            $this->showStartMenu();
            return;
        }

        if ($this->telegram->text() === '/profile') {
            $this->showProfileMenu();
            return;
        }

        if ($this->telegram->text() === '/set_name') {
            $this->handleEditName();
            return;
        }

        $last_command = $this->fetchLatestCommand();

        if ($last_command?->command === '/set_name') {
            var_dump('safsaf');
            $this->handleUpdateName($last_command);
            return;
        }


        $this->sendChatTexts();
    }

    private function showStartMenu(): void
    {
        $keyboard = [
            ['شروع چت ناشناس' => '/request']
        ];
        $this->telegram->sendMessage('برای شروع چت ناشناس روی این دکمه کلیک کن', $keyboard);
    }

    private function handleRequestForChat(): void
    {
        $loading_message = $this->showLoading($this->telegram);

        $requests = (new ChatRequest)->searchForRequests($this->telegram->chatId());

        if (!$requests) {
            (new ChatRequest)->create([
                'chat_id' => $this->telegram->chatId(),
                'time' => now()
            ]);

            $this->telegram->editMessage("درخواست شما ایجاد شد. منتظر بمانید...", message_to_edit: $loading_message);
            return;
        }

        $matchedRequest = $requests[0];

        (new ChatMatch())->create([
            'user_1' => $this->telegram->chatId(),
            'user_2' => $matchedRequest->chat_id
        ]);

        $this->telegram->editMessage("چت ناشناس شما آغاز شد. پیام خود را بنویسید", message_to_edit: $loading_message);
        $this->telegram->sendMessage("چت ناشناس شما آغاز شد. پیام خود را بنویسید", chat_id: $matchedRequest->chat_id);
    }

    private function stopOngoingChat(): void
    {
        $chat = (new ChatMatch())->searchForOngoingChat($this->telegram->chatId());

        if (!$chat) {
            $this->telegram->sendMessage("دستور اشتباه است");
            return;
        }

        $chat->close();
    }

    /**
     * @return void
     */
    public function sendChatTexts(): void
    {
        $match = (new ChatMatch())->searchForOngoingChat($this->telegram->chatId());

        if (!$match) {
            $this->telegram->sendMessage("برای شروع چت ناشناس دستور /start را وارد کنید");
            return;
        }

        $receiver = $match->user_1 == $this->telegram->chatId() ? $match->user_2 : $match->user_1;
        $this->telegram->sendMessage($this->telegram->text(), chat_id: $receiver);
    }

    private function showProfileMenu(): void
    {
        $menu = [
            ['نام شما' => '/set_name', 'سن شما' => '/set_age'],
            ['جنسیت شما' => '/set_sex']
        ];

        $this->telegram->sendMessage('برای تغییر هر یک از تنظیمات، روی گزینه های زیر کلیک کن', $menu);
    }

    private function handleEditName(): void
    {
        $this->insertCommand('/set_name');
        $this->telegram->sendMessage("نام خود را وارد کنید:");
    }

    private function insertCommand(string $command): void
    {
        (new Command)->insert($this->telegram->chatId(), $command);
    }

    private function fetchLatestCommand(): ?Command
    {
        return (new Command)->byChatId($this->telegram->chatId());
    }

    private function handleUpdateName(Command $command): void
    {
        $this->user->update([
            'name' => $this->telegram->text()
        ]);
        $command->close();
        $this->telegram->sendMessage("✅ نام با موفقیت ویرایش شد");
    }

    private function findOrCreateUser(): User|bool|null
    {
        $user = (new User)->where('chat_id', $this->telegram->chatId());

        if (! $user) {
            $user = (new User)->insert($this->telegram->chatId());
        }

        return $user;
    }
}