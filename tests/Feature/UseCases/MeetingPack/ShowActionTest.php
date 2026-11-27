<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use App\UseCases\MeetingPack\ShowAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_specified_meeting_pack(): void
    {
        $plan = MeetingPack::factory()->create();

        $result = app(ShowAction::class)($plan);

        $this->assertSame($plan->id, $result->id);
    }

    public function test_loads_created_by_relationship(): void
    {
        $creator = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create([
            'created_by_user_id' => $creator->id,
        ]);

        $result = app(ShowAction::class)($plan);

        $this->assertTrue($result->relationLoaded('createdBy'));
        $this->assertSame($creator->id, $result->createdBy->id);
    }

    public function test_loads_updated_by_relationship(): void
    {
        $updater = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create([
            'updated_by_user_id' => $updater->id,
        ]);

        $result = app(ShowAction::class)($plan);

        $this->assertTrue($result->relationLoaded('updatedBy'));
        $this->assertSame($updater->id, $result->updatedBy->id);
    }
}
