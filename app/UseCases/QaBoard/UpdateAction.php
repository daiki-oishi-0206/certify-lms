<?php

declare(strict_types=1);

namespace App\UseCases\QaBoard;

use App\Models\QaThread;

final class UpdateAction
{
    public function __invoke(
        QaThread $thread,
        string $title,
        string $body,
    ): QaThread {
        $thread->update([
            'title' => $title,
            'body' => $body,
        ]);

        return $thread;
    }
}
