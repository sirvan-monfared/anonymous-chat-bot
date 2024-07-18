<?php

namespace App\Models;

class ChatMatch extends Model
{
    protected string $table = 'chat_matches';
    protected bool $timestamps = false;

    CONST ONGOING = 1;
    CONST FINISHED = 2;

    public function searchForOngoingChat(int $chatId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE  status=:status AND (user_1=:chat_id OR user_2=:chat_id) ORDER BY `id` DESC LIMIT 1";

        return $this->db->prepare($sql, [
            'chat_id' => $chatId,
            'status' => ChatMatch::ONGOING
        ], __CLASS__)->find();
    }

    public function close()
    {
        return $this->update([
            'status' => ChatMatch::FINISHED
        ]);
    }
}