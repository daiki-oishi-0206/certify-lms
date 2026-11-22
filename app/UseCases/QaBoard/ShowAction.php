<?php

declare(strict_types=1);

namespace App\UseCases\QaBoard;

use App\Models\QaThread;

/**
 * Q&Aスレッドの詳細と返信一覧を取得するユースケース。
 */
final class ShowAction
{
    public function __invoke(QaThread $thread): QaThread
    {
        return $thread->load([
            'user',
            'certification',
            'replies' => function ($query) {
                $query->with('user')
                    ->orderBy('created_at')
                    ->orderBy('id');
            },
        ]);
    }
}
