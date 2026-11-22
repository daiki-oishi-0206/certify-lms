<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaReplyTest extends TestCase
{
    use RefreshDatabase;

    // No.3 QaReplyモデルの正常動作
    public function test_qa_reply_can_be_saved(): void
    {
        $certification = Certification::factory()->create();
        $owner = User::factory()->student()->inProgress()->create();
        $author = User::factory()->student()->inProgress()->create();

        $thread = QaThread::factory()->create([
            'certification_id' => $certification->id,
            'user_id' => $owner->id,
        ]);

        $reply = QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'user_id' => $author->id,
        ]);

        $this->assertDatabaseHas('qa_replies', [
            'id' => $reply->id,
            'qa_thread_id' => $thread->id,
            'user_id' => $author->id,
        ]);
    }

    // No.4 QaReplyモデルのリレーション
    public function test_qa_reply_can_retrieve_related_models(): void
    {
        $certification = Certification::factory()->create();
        $owner = User::factory()->student()->inProgress()->create();
        $author = User::factory()->student()->inProgress()->create();

        $thread = QaThread::factory()->create([
            'certification_id' => $certification->id,
            'user_id' => $owner->id,
        ]);

        $reply = QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'user_id' => $author->id,
        ]);

        $this->assertTrue($reply->thread->is($thread));
        $this->assertTrue($reply->user->is($author));
    }
}
