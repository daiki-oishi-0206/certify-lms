<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use App\Models\User;

final class StoreAction
{
    /**
     * 面談パックを新規作成するユースケース。
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
    public function __invoke(array $data, User $user): MeetingPack
    {
        return MeetingPack::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'meeting_count' => $data['meeting_count'],
            'price' => $data['price'],
            'stripe_price_id' => $data['stripe_price_id'] ?? null,
            'status' => MeetingPackStatus::Draft,
            'sort_order' => $data['sort_order'] ?? 0,
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
        ]);
    }
}
