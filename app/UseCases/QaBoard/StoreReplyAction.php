<?php

declare(strict_types=1);

namespace App\UseCases\QaBoard;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;

final class StoreReplyAction
{
    public function __invoke(
        QaThread $thread,
        User $user,
        string $body,
    ): QaReply {
        return $thread->replies()->create([
            'user_id' => $user->id,
            'body' => $body,
        ]);
    }
}
