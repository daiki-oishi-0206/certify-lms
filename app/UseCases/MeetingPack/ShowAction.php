<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Models\MeetingPack;

/**
 * 面談パックの詳細を取得するユースケース。
 */
final class ShowAction
{
    public function __invoke(MeetingPack $plan): MeetingPack
    {
        return $plan->load([
            'createdBy',
            'updatedBy',
        ]);
    }
}
