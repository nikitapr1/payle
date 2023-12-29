<?php

namespace App\Services;

use App\Dto\NextMessage;
use App\Models\MessagesSupportHandle;


class NextMessageService
{
    public static function addNextStepHandler(string $chatId, string $data, string $command): void
    {
        MessagesSupportHandle::query()->create([
            'chat_id' => $chatId,
            'data' => $data,
            'action' => $command
        ]);
    }

    public static function getNextStepHandler(string $chatId): ?NextMessage
    {
        if (!$messageForHandle = MessagesSupportHandle::query()->where([
            'chat_id' => $chatId,
        ])->first()) {
            return null;
        }

        return new NextMessage($messageForHandle->action, unserialize($messageForHandle->data));
    }

    public static function deleteNextStepHandler(string $chatId, string $command): void
    {
        $handlers = MessagesSupportHandle::query()->where([
            'chat_id' => $chatId,
            'action' => $command
        ])->delete();


    }

    public static function deleteAllHandlersForChat(string $chatId): void{
         $handlers = MessagesSupportHandle::query()->where([
            'chat_id' => $chatId
        ])->delete();

    }
}
