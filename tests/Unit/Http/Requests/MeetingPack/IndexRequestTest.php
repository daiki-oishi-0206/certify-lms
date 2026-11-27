<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 面談パック一覧 IndexRequest のバリデーション検証。
 * keyword / status / page のバリデーションを確認する。
 */
class IndexRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_empty_filters(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.meeting-packs.index'));

        // Assert
        $response->assertSuccessful();
    }

    public function test_validation_passes_with_all_filters_set(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.index', [
            'keyword' => str_repeat('a', 100),
            'status' => MeetingPackStatus::Draft->value,
            'page' => 1,
        ]));

        // Assert
        $response->assertSuccessful();
    }

    public function test_validation_fails_with_101_character_keyword(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();

        // Act
        $response = $this->actingAs($admin)->getJson(route('admin.meeting-packs.index', [
            'keyword' => str_repeat('a', 101),
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('keyword');
    }

    public function test_validation_fails_with_invalid_status(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();

        // Act
        $response = $this->actingAs($admin)->getJson(route('admin.meeting-packs.index', [
            'status' => 'unknown',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }

    public function test_validation_fails_with_page_zero(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();

        // Act
        $response = $this->actingAs($admin)->getJson(route('admin.meeting-packs.index', [
            'page' => 0,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('page');
    }

    public function test_validation_fails_with_non_integer_page(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();

        // Act
        $response = $this->actingAs($admin)->getJson(route('admin.meeting-packs.index', [
            'page' => 'abc',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('page');
    }
}
