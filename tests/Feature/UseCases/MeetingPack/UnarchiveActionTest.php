<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Exceptions\MeetingPack\MeetingPackInvalidTransitionException;
use App\Models\MeetingPack;
use App\Models\User;
use App\UseCases\MeetingPack\UnarchiveAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnarchiveActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unarchives_archived_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->archived()->create();

        $result = app(UnarchiveAction::class)($plan, $admin);

        $this->assertSame(MeetingPackStatus::Draft, $result->status);
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Draft->value,
        ]);
    }

    public function test_records_unarchiver_as_updater(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->archived()->create();

        $result = app(UnarchiveAction::class)($plan, $admin);

        $this->assertSame($admin->id, $result->updated_by_user_id);
    }

    public function test_cannot_unarchive_draft_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $this->expectException(MeetingPackInvalidTransitionException::class);

        try {
            app(UnarchiveAction::class)($plan, $admin);
        } finally {
            $this->assertSame(
                MeetingPackStatus::Draft,
                $plan->fresh()->status,
            );
        }
    }

    public function test_cannot_unarchive_published_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create();

        $this->expectException(MeetingPackInvalidTransitionException::class);

        try {
            app(UnarchiveAction::class)($plan, $admin);
        } finally {
            $this->assertSame(
                MeetingPackStatus::Published,
                $plan->fresh()->status,
            );
        }
    }
}
