<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaThreadTest extends TestCase
{
    use RefreshDatabase;

    // No.1 QaThreadモデルの正常動作
    public function test_qa_thread_can_be_saved(): void
    {
        $certification = Certification::factory()->create();
        $user = User::factory()->student()->inProgress()->create();

        $thread = QaThread::factory()->create([
            'certification_id' => $certification->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'certification_id' => $certification->id,
            'user_id' => $user->id,
        ]);
    }

    // No.2 QaThreadモデルのリレーション
    public function test_qa_thread_can_retrieve_related_models(): void
    {
        $certification = Certification::factory()->create();
        $user = User::factory()->student()->inProgress()->create();

        $thread = QaThread::factory()->create([
            'certification_id' => $certification->id,
            'user_id' => $user->id,
        ]);

        $reply = QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($thread->certification->is($certification));
        $this->assertTrue($thread->user->is($user));
        $this->assertTrue($thread->replies->contains($reply));
    }
}
