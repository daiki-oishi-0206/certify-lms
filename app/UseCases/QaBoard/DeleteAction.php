<?php

declare(strict_types=1);

namespace App\UseCases\QaBoard;

use App\Models\QaThread;

final class DeleteAction
{
    public function __invoke(QaThread $thread): void
    {
        $thread->delete();
    }
}
