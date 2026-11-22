<?php

declare(strict_types=1);

namespace App\UseCases\QaBoard;

use App\Models\QaReply;

final class UpdateReplyAction
{
    public function __invoke(
        QaReply $reply,
        string $body,
    ): QaReply {
        $reply->update([
            'body' => $body,
        ]);

        return $reply;
    }
}
