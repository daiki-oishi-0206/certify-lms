<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Exceptions\MeetingPack\MeetingPackInvalidTransitionException;
use App\Models\MeetingPack;
use App\Models\User;
use App\UseCases\MeetingPack\PublishAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishes_draft_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $result = app(PublishAction::class)($plan, $admin);

        $this->assertSame(MeetingPackStatus::Published, $result->status);
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Published->value,
        ]);
    }

    public function test_records_publisher_as_updater(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $result = app(PublishAction::class)($plan, $admin);

        $this->assertSame($admin->id, $result->updated_by_user_id);
    }

    public function test_cannot_publish_already_published_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create();

        $this->expectException(MeetingPackInvalidTransitionException::class);

        try {
            app(PublishAction::class)($plan, $admin);
        } finally {
            $this->assertSame(
                MeetingPackStatus::Published,
                $plan->fresh()->status,
            );
        }
    }

    public function test_cannot_publish_archived_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->archived()->create();

        $this->expectException(MeetingPackInvalidTransitionException::class);

        try {
            app(PublishAction::class)($plan, $admin);
        } finally {
            $this->assertSame(
                MeetingPackStatus::Archived,
                $plan->fresh()->status,
            );
        }
    }
}
