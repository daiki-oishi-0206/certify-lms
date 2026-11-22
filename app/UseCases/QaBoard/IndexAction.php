<?php

declare(strict_types=1);

namespace App\UseCases\QaBoard;

use App\Enums\CertificationStatus;
use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * 閲覧権限に応じてQ&Aスレッド一覧を取得するユースケース。
 */
final class IndexAction
{
    public function __invoke(
        User $viewer,
        ?string $keyword,
        ?string $certificationId,
        ?QaThreadStatus $status,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = QaThread::query()
            ->with(['user', 'certification'])
            ->withCount('replies');

        // 閲覧者のロールに応じて表示対象の資格を絞り込む
        if ($viewer->role === UserRole::Student) {
            $query->whereHas('certification', function (Builder $query) {
                $query->where('status', CertificationStatus::Published);
            });
        } elseif ($viewer->role === UserRole::Coach) {
            $query->whereHas('certification', function (Builder $query) use ($viewer) {
                $query->where('status', CertificationStatus::Published)
                    ->whereHas('coaches', function (Builder $query) use ($viewer) {
                        $query->where('users.id', $viewer->id);
                    });
            });
        }

        // キーワード検索
        if ($keyword !== null && $keyword !== '') {
            $query->where(function (Builder $query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('body', 'like', "%{$keyword}%");
            });
        }

        // 資格による絞り込み
        if ($certificationId !== null && $certificationId !== '') {
            $query->where('certification_id', $certificationId);
        }

        // 解決状態による絞り込み
        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
