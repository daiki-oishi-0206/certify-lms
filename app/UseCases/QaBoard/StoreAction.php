<?php

declare(strict_types=1);

namespace App\UseCases\QaBoard;

use App\Models\QaThread;
use App\Models\User;

final class StoreAction
{
    public function __invoke(
        User $user,
        string $certificationId,
        string $title,
        string $body,
    ): QaThread {
        return QaThread::create([
            'user_id' => $user->id,
            'certification_id' => $certificationId,
            'title' => $title,
            'body' => $body,
        ]);
    }
}
