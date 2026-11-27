<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'name' => '新規面談パック',
            'description' => '面談用の追加パック',
            'meeting_count' => 5,
            'price' => 12000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 1,
        ];
    }

    public function test_admin_can_create_meeting_pack_as_draft(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.meeting-packs.store'), $this->payload());

        $response->assertRedirect(route('admin.meeting-packs.index'));
        $response->assertSessionHas('success', '面談パックを登録しました。');

        $this->assertDatabaseHas('meeting_packs', [
            'name' => '新規面談パック',
            'meeting_count' => 5,
            'price' => 12000,
            'status' => 'draft',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_required_fields_are_validated(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->payload();

        unset($payload['name'], $payload['meeting_count'], $payload['price']);

        $response = $this->actingAs($admin)
            ->post(route('admin.meeting-packs.store'), $payload);

        $response->assertSessionHasErrors([
            'name',
            'meeting_count',
            'price',
        ]);
    }

    public function test_coach_cannot_create_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)
            ->post(route('admin.meeting-packs.store'), $this->payload());

        $response->assertForbidden();
    }
}
