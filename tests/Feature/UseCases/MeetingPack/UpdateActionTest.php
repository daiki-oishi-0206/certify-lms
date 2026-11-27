<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use App\Models\User;
use App\UseCases\MeetingPack\UpdateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_meeting_pack_with_specified_data(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create();

        $data = [
            'name' => 'プレミアム面談パック',
            'description' => '更新後の説明文',
            'meeting_count' => 10,
            'price' => 30000,
            'stripe_price_id' => 'price_updated_123',
            'sort_order' => 20,
        ];

        $result = app(UpdateAction::class)($plan, $data, $admin);

        $this->assertSame($plan->id, $result->id);
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'name' => 'プレミアム面談パック',
            'description' => '更新後の説明文',
            'meeting_count' => 10,
            'price' => 30000,
            'stripe_price_id' => 'price_updated_123',
            'sort_order' => 20,
        ]);
    }

    public function test_optional_fields_are_set_to_null_when_omitted(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create([
            'description' => '更新前の説明文',
            'stripe_price_id' => 'price_before_123',
        ]);

        $result = app(UpdateAction::class)($plan, [
            'name' => '面談パック',
            'meeting_count' => 5,
            'price' => 15000,
        ], $admin);

        $this->assertNull($result->description);
        $this->assertNull($result->stripe_price_id);
    }

    public function test_sort_order_defaults_to_zero_when_omitted(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create([
            'sort_order' => 10,
        ]);

        $result = app(UpdateAction::class)($plan, [
            'name' => '面談パック',
            'meeting_count' => 5,
            'price' => 15000,
        ], $admin);

        $this->assertSame(0, $result->sort_order);
    }

    public function test_records_updater(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create();

        $result = app(UpdateAction::class)($plan, [
            'name' => '面談パック',
            'meeting_count' => 5,
            'price' => 15000,
        ], $admin);

        $this->assertSame($admin->id, $result->updated_by_user_id);
    }

    public function test_does_not_change_status_when_updating(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create();

        $result = app(UpdateAction::class)($plan, [
            'name' => '更新後の面談パック',
            'meeting_count' => 5,
            'price' => 15000,
        ], $admin);

        $this->assertSame(MeetingPackStatus::Published, $result->status);
    }
}
