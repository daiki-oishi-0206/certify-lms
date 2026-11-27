<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 面談パック更新 UpdateRequest のバリデーション検証。
 */
class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_input(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create();

        $response = $this->actingAs($admin)
            ->patch(route('admin.meeting-packs.update', $plan), $this->validPayload());

        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'name' => '更新後の面談パック',
            'meeting_count' => 10,
            'price' => 20000,
        ]);
    }

    public function test_validation_fails_with_missing_required_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create();
        $payload = $this->validPayload();

        unset($payload['name'], $payload['meeting_count'], $payload['price']);

        $response = $this->actingAs($admin)
            ->patchJson(route('admin.meeting-packs.update', $plan), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
            'meeting_count',
            'price',
        ]);
    }

    public function test_validation_fails_with_exceeded_character_limits(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create();
        $payload = $this->validPayload();

        $payload['name'] = str_repeat('a', 101);
        $payload['description'] = str_repeat('a', 2001);
        $payload['stripe_price_id'] = str_repeat('a', 256);

        $response = $this->actingAs($admin)
            ->patchJson(route('admin.meeting-packs.update', $plan), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
            'description',
            'stripe_price_id',
        ]);
    }

    public function test_validation_fails_with_values_below_minimum(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create();
        $payload = $this->validPayload();

        $payload['meeting_count'] = 0;
        $payload['price'] = -1;
        $payload['sort_order'] = -1;

        $response = $this->actingAs($admin)
            ->patchJson(route('admin.meeting-packs.update', $plan), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'meeting_count',
            'price',
            'sort_order',
        ]);
    }

    public function test_validation_fails_with_values_above_maximum(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create();
        $payload = $this->validPayload();

        $payload['meeting_count'] = 101;
        $payload['price'] = 1000001;

        $response = $this->actingAs($admin)
            ->patchJson(route('admin.meeting-packs.update', $plan), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'meeting_count',
            'price',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => '更新後の面談パック',
            'description' => '更新後の説明',
            'meeting_count' => 10,
            'price' => 20000,
            'stripe_price_id' => 'price_test_updated',
            'sort_order' => 2,
        ];
    }
}
