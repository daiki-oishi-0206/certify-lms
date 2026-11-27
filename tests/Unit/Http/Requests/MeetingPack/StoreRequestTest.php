<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\MeetingPack;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 面談パック新規作成 StoreRequest のバリデーション検証。
 */
class StoreRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_input(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.meeting-packs.store'), $this->validPayload());

        $response->assertRedirect(route('admin.meeting-packs.index'));
    }

    public function test_validation_fails_with_missing_required_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->validPayload();

        unset($payload['name'], $payload['meeting_count'], $payload['price']);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.meeting-packs.store'), $payload);

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
        $payload = $this->validPayload();

        $payload['name'] = str_repeat('a', 101);
        $payload['description'] = str_repeat('a', 2001);
        $payload['stripe_price_id'] = str_repeat('a', 256);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.meeting-packs.store'), $payload);

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
        $payload = $this->validPayload();

        $payload['meeting_count'] = 0;
        $payload['price'] = -1;
        $payload['sort_order'] = -1;

        $response = $this->actingAs($admin)
            ->postJson(route('admin.meeting-packs.store'), $payload);

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
        $payload = $this->validPayload();

        $payload['meeting_count'] = 101;
        $payload['price'] = 1000001;

        $response = $this->actingAs($admin)
            ->postJson(route('admin.meeting-packs.store'), $payload);

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
            'name' => '面談パック',
            'description' => '面談用の追加パック',
            'meeting_count' => 5,
            'price' => 12000,
            'stripe_price_id' => 'price_test_123',
            'sort_order' => 1,
        ];
    }
}
