<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Exceptions\MeetingPack\MeetingPackInvalidTransitionException;
use App\Models\MeetingPack;
use App\Models\User;
use App\UseCases\MeetingPack\ArchiveAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_archives_published_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create();

        $result = app(ArchiveAction::class)($plan, $admin);

        $this->assertSame(MeetingPackStatus::Archived, $result->status);
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Archived->value,
        ]);
    }

    public function test_records_archiver_as_updater(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create();

        $result = app(ArchiveAction::class)($plan, $admin);

        $this->assertSame($admin->id, $result->updated_by_user_id);
    }

    public function test_cannot_archive_draft_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $this->expectException(MeetingPackInvalidTransitionException::class);

        try {
            app(ArchiveAction::class)($plan, $admin);
        } finally {
            $this->assertSame(
                MeetingPackStatus::Draft,
                $plan->fresh()->status,
            );
        }
    }

    public function test_cannot_archive_already_archived_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->archived()->create();

        $this->expectException(MeetingPackInvalidTransitionException::class);

        try {
            app(ArchiveAction::class)($plan, $admin);
        } finally {
            $this->assertSame(
                MeetingPackStatus::Archived,
                $plan->fresh()->status,
            );
        }
    }
}
