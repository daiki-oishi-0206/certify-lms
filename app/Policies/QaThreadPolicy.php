<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;

/**
 * Q&Aスレッドに対する認可ポリシー。
 */
class QaThreadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin
            || $this->isActiveStudentOrCoach($user);
    }

    public function view(User $user, QaThread $thread): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if (! $this->isActiveStudentOrCoach($user)) {
            return false;
        }

        $thread->loadMissing('certification');

        $certification = $thread->certification;

        if (
            $certification === null
            || $certification->status !== CertificationStatus::Published
        ) {
            return false;
        }

        return match ($user->role) {
            UserRole::Student => true,
            UserRole::Coach => $this->isAssignedCoach($certification, $user),
            default => false,
        };
    }

    public function create(User $user, Certification $certification): bool
    {
        return $user->role === UserRole::Student
            && $user->status === UserStatus::InProgress
            && $certification->status === CertificationStatus::Published;
    }

    public function update(User $user, QaThread $thread): bool
    {
        return $user->role === UserRole::Student
            && $this->isOwner($user, $thread)
            && $this->view($user, $thread);
    }

    public function delete(User $user, QaThread $thread): bool
    {
        // 管理者は資格の公開状態や返信数に関係なく削除可能
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $user->role === UserRole::Student
            && $this->isOwner($user, $thread)
            && $this->view($user, $thread)
            && ! $thread->replies()->exists();
    }

    public function resolve(User $user, QaThread $thread): bool
    {
        return $user->role === UserRole::Student
            && $this->isOwner($user, $thread)
            && $this->view($user, $thread);
    }

    public function unresolve(User $user, QaThread $thread): bool
    {
        return $this->resolve($user, $thread);
    }

    private function isOwner(User $user, QaThread $thread): bool
    {
        return $thread->user_id === $user->id;
    }

    private function isActiveStudentOrCoach(User $user): bool
    {
        return $user->status === UserStatus::InProgress
            && in_array($user->role, [
                UserRole::Student,
                UserRole::Coach,
            ], true);
    }

    private function isAssignedCoach(
        Certification $certification,
        User $coach
    ): bool {
        $certification->loadMissing('coaches');

        return $certification->coaches->contains('id', $coach->id);
    }
}
