<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;

final class UpdateAction
{
    /**
     * 面談パックを更新するユースケース。
     *
     * @param array{
     *     name: string,
     *     description?: string|null,
     *     meeting_count: int,
     *     price: int,
     *     stripe_price_id?: string|null,
     *     sort_order?: int|null
     * } $data
     */
    public function __invoke(
        MeetingPack $plan,
        array $data,
        User $user,
    ): MeetingPack {
        $plan->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'meeting_count' => $data['meeting_count'],
            'price' => $data['price'],
            'stripe_price_id' => $data['stripe_price_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'updated_by_user_id' => $user->id,
        ]);

        return $plan->refresh();
    }
}
