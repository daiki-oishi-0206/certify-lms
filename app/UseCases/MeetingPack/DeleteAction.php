<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use Illuminate\Support\Facades\DB;
use LogicException;

final class DeleteAction
{
    public function __invoke(MeetingPack $plan): void
    {
        if ($plan->status === MeetingPackStatus::Published) {
            throw new LogicException('公開中の面談パックは削除できません。');
        }

        DB::transaction(function () use ($plan): void {
            $plan->delete();
        });
    }
}
