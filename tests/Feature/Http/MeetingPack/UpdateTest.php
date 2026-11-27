<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();

        $plan = MeetingPack::factory()->create([
            'name' => '更新前のパック',
            'meeting_count' => 5,
            'price' => 10000,
        ]);

        $payload = [
            'name' => '更新後のパック',
            'description' => '更新後の説明',
            'meeting_count' => 10,
            'price' => 20000,
            'stripe_price_id' => 'price_updated',
            'sort_order' => 2,
        ];

        $response = $this->actingAs($admin)
            ->patch(route('admin.meeting-packs.update', $plan), $payload);

        $response->assertRedirect(route('admin.meeting-packs.show', $plan));

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'name' => '更新後のパック',
            'description' => '更新後の説明',
            'meeting_count' => 10,
            'price' => 20000,
            'stripe_price_id' => 'price_updated',
            'sort_order' => 2,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_status_is_unchanged_even_if_payload_includes_status(): void
    {
        $admin = User::factory()->admin()->create();

        $plan = MeetingPack::factory()->published()->create();

        $payload = [
            'name' => $plan->name,
            'description' => $plan->description,
            'meeting_count' => $plan->meeting_count,
            'price' => $plan->price,
            'stripe_price_id' => $plan->stripe_price_id,
            'sort_order' => $plan->sort_order,
            'status' => 'draft',
        ];

        $this->actingAs($admin)
            ->patch(route('admin.meeting-packs.update', $plan), $payload);

        $this->assertSame('published', $plan->fresh()->status->value);
    }

    public function test_coach_cannot_update_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();

        $plan = MeetingPack::factory()->create();

        $response = $this->actingAs($coach)
            ->patch(route('admin.meeting-packs.update', $plan), [
                'name' => '変更しようとしたパック',
                'meeting_count' => 10,
                'price' => 20000,
            ]);

        $response->assertForbidden();
    }

    public function test_student_cannot_update_meeting_pack(): void
    {
        $student = User::factory()->student()->create();

        $plan = MeetingPack::factory()->create();

        $response = $this->actingAs($student)
            ->patch(route('admin.meeting-packs.update', $plan), [
                'name' => '変更しようとしたパック',
                'meeting_count' => 10,
                'price' => 20000,
            ]);

        $response->assertForbidden();
    }
}
