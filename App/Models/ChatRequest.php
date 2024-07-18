<?php

namespace App\Models;

use Carbon\Carbon;

class ChatRequest extends Model
{
    protected string $table = 'chat_requests';
    protected bool $timestamps = false;

    public function searchForRequests(mixed $chatId)
    {
        $sql =  "SELECT * FROM {$this->table} WHERE `chat_id`!=:chat_id AND `time`>=:time ";

        return $this->db->prepare($sql, [
            'chat_id' => $chatId,
            'time' => Carbon::now()->subMinutes(3)->format('Y/m/d H:i:s')
        ], __CLASS__)->all();
    }


}