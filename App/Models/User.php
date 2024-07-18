<?php

namespace App\Models;

class User extends Model
{
    protected string $table = 'users';

    public function insert(int $chatId): ?User
    {
        return $this->create([
            'chat_id' => $chatId
        ]);
    }
}