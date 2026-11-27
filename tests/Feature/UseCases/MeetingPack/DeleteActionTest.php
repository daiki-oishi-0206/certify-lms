<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Exceptions\MeetingPack\MeetingPackNotDeletableException;
use App\Models\MeetingPack;
use App\UseCases\MeetingPack\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_draft_meeting_pack(): void
    {
        $plan = MeetingPack::factory()->draft()->create();

        app(DeleteAction::class)($plan);

        $this->assertDatabaseMissing('meeting_packs', [
            'id' => $plan->id,
        ]);
    }

    public function test_deletes_archived_meeting_pack(): void
    {
        $plan = MeetingPack::factory()->archived()->create();

        app(DeleteAction::class)($plan);

        $this->assertDatabaseMissing('meeting_packs', [
            'id' => $plan->id,
        ]);
    }

    public function test_cannot_delete_published_meeting_pack(): void
    {
        $plan = MeetingPack::factory()->published()->create();

        $this->expectException(MeetingPackNotDeletableException::class);

        try {
            app(DeleteAction::class)($plan);
        } finally {
            $this->assertDatabaseHas('meeting_packs', [
                'id' => $plan->id,
                'status' => MeetingPackStatus::Published->value,
            ]);
        }
    }
}
