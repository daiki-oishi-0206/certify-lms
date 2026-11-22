<?php

declare(strict_types=1);

namespace App\UseCases\QaBoard;

use App\Enums\QaThreadStatus;
use App\Models\QaThread;

final class UpdateStatusAction
{
    public function __invoke(
        QaThread $thread,
        QaThreadStatus $status,
    ): QaThread {
        $thread->update([
            'status' => $status,
            'resolved_at' => $status === QaThreadStatus::Resolved
                ? now()
                : null,
        ]);

        return $thread;
    }
}
