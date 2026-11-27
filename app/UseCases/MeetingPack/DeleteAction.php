<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Exceptions\MeetingPack\MeetingPackNotDeletableException;
use App\Models\MeetingPack;
use Illuminate\Support\Facades\DB;

final class DeleteAction
{
    /**
     * @throws MeetingPackNotDeletableException 公開中の面談パックは削除不可
     */
    public function __invoke(MeetingPack $plan): void
    {
        if ($plan->status === MeetingPackStatus::Published) {
            throw new MeetingPackNotDeletableException;
        }

        DB::transaction(function () use ($plan): void {
            $plan->delete();
        });
    }
}
