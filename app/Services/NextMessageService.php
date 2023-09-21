<?php

namespace App\Services;

use App\Dto\NextMessage;
use App\Models\MessagesSupportHandle;

class NextMessageService
{
    public static function handle(string $chatId, callable $handler, array $data, string $paramKey): void {
        MessagesSupportHandle::query()->create([
            'chat_id' => $chatId,
            'action' => serialize($handler),
            'data' => $data
        ]);
    }

    public static function getNextMessageHandler(string $chatId, string $message): ?NextMessage {
        MessagesSupportHandle::query()->where();


    }
}
