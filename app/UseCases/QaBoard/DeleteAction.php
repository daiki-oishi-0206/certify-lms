<?php

declare(strict_types=1);

namespace App\UseCases\QaBoard;

use App\Models\QaThread;
use Illuminate\Support\Facades\DB;

final class DeleteAction
{
    public function __invoke(QaThread $thread): void
    {
        DB::transaction(function () use ($thread): void {
            $thread->replies()->delete();
            $thread->delete();
        });
    }
}
