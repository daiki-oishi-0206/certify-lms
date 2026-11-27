<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use App\Models\User;
use App\UseCases\MeetingPack\StoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_meeting_pack_with_specified_data(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'name' => 'プレミアム面談パック',
            'description' => '特別コーチングプラン',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 10,
        ];

        $result = app(StoreAction::class)($data, $admin);

        $this->assertInstanceOf(MeetingPack::class, $result);
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $result->id,
            'name' => 'プレミアム面談パック',
            'description' => '特別コーチングプラン',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 10,
        ]);
    }

    public function test_new_meeting_pack_has_draft_status(): void
    {
        $admin = User::factory()->admin()->create();

        $result = app(StoreAction::class)([
            'name' => '面談パック',
            'meeting_count' => 3,
            'price' => 9000,
        ], $admin);

        $this->assertSame(MeetingPackStatus::Draft, $result->fresh()->status);
    }

    public function test_sort_order_defaults_to_zero_when_omitted(): void
    {
        $admin = User::factory()->admin()->create();

        $result = app(StoreAction::class)([
            'name' => '面談パック',
            'meeting_count' => 3,
            'price' => 9000,
        ], $admin);

        $this->assertSame(0, $result->fresh()->sort_order);
    }

    public function test_records_creator_and_updater(): void
    {
        $admin = User::factory()->admin()->create();

        $result = app(StoreAction::class)([
            'name' => '面談パック',
            'meeting_count' => 3,
            'price' => 9000,
        ], $admin);

        $this->assertSame($admin->id, $result->fresh()->created_by_user_id);
        $this->assertSame($admin->id, $result->fresh()->updated_by_user_id);
    }
}
