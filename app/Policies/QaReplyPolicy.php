<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;

/**
 * Q&A返信に対する認可ポリシー。
 */
class QaReplyPolicy
{
    public function __construct(
        private QaThreadPolicy $threadPolicy
    ) {}

    public function create(User $user, QaThread $thread): bool
    {
        return $this->isActiveStudentOrCoach($user)
            && $this->threadPolicy->view($user, $thread);
    }

    public function update(User $user, QaReply $reply): bool
    {
        if (
            ! $this->isActiveStudentOrCoach($user)
            || $reply->user_id !== $user->id
        ) {
            return false;
        }

        $reply->loadMissing('thread');

        return $reply->thread !== null
            && $this->threadPolicy->view($user, $reply->thread);
    }

    public function delete(User $user, QaReply $reply): bool
    {
        // 管理者は任意の返信を削除可能
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if (
            ! $this->isActiveStudentOrCoach($user)
            || $reply->user_id !== $user->id
        ) {
            return false;
        }

        $reply->loadMissing('thread');

        return $reply->thread !== null
            && $this->threadPolicy->view($user, $reply->thread);
    }

    private function isActiveStudentOrCoach(User $user): bool
    {
        return $user->status === UserStatus::InProgress
            && in_array($user->role, [
                UserRole::Student,
                UserRole::Coach,
            ], true);
    }
}
