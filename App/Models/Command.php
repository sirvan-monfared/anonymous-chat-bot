<?php

namespace App\Models;

use Carbon\Carbon;

class Command extends Model
{
    protected string $table = 'commands';
    protected bool $timestamps = false;

    CONST PENDING = 1;
    CONST FINISHED = 2;

    public function insert(int $chat_id, string $command): ?Command
    {
        return $this->create([
            'chat_id' => $chat_id,
            'command' => $command,
            'status' => Command::PENDING,
            'time' => now()
        ]);
    }

    public function byChatId(int $chatId): ?Command
    {
        $sql = "SELECT * FROM {$this->table} WHERE chat_id=:chat_id AND status=:status AND time >= :time ORDER BY `id` DESC LIMIT 1";

        return $this->db->prepare($sql, [
            'chat_id' => $chatId,
            'status' => Command::PENDING,
            'time' => Carbon::now()->subMinutes(2),
        ], __CLASS__)->find();
    }

    public function close(): Command
    {
        return $this->update([
            'status' => Command::FINISHED
        ]);
    }
}