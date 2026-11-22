<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Certification;
use App\Models\CertificationCoachAssignment;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use App\Policies\QaReplyPolicy;
use App\Policies\QaThreadPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QaReplyPolicyTest extends TestCase
{
    use RefreshDatabase;

    private QaReplyPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new QaReplyPolicy(new QaThreadPolicy);
    }

    // No.18 受講生が返信を投稿
    public function test_student_can_create_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = $this->createThread();

        $this->assertTrue($this->policy->create($student, $thread));
    }

    // No.19 受講生が自分のスレッドに返信を投稿
    public function test_student_can_reply_to_own_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = $this->createThread($student);

        $this->assertTrue($this->policy->create($student, $thread));
    }

    // No.20 担当コーチが返信を投稿
    public function test_assigned_coach_can_create_reply(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->published()->create();

        $this->assignCoach($certification, $coach);

        $thread = $this->createThread(
            User::factory()->student()->inProgress()->create(),
            $certification,
        );

        $this->assertTrue($this->policy->create($coach, $thread));
    }

    // No.21 担当外コーチが返信を投稿
    public function test_unassigned_coach_cannot_create_reply(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $thread = $this->createThread();

        $this->assertFalse($this->policy->create($coach, $thread));
    }

    // No.22 管理者が返信を投稿
    public function test_admin_cannot_create_reply(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = $this->createThread();

        $this->assertFalse($this->policy->create($admin, $thread));
    }

    // No.23 招待中の受講生が返信を投稿
    public function test_invited_student_cannot_create_reply(): void
    {
        $student = User::factory()->student()->invited()->create();
        $thread = $this->createThread();

        $this->assertFalse($this->policy->create($student, $thread));
    }

    // No.24 卒業済みの受講生が返信を投稿
    public function test_graduated_student_cannot_create_reply(): void
    {
        $student = User::factory()->student()->graduated()->create();
        $thread = $this->createThread();

        $this->assertFalse($this->policy->create($student, $thread));
    }

    // No.25 退会済みの受講生が返信を投稿
    public function test_withdrawn_student_cannot_create_reply(): void
    {
        $student = User::factory()->student()->withdrawn()->create();
        $thread = $this->createThread();

        $this->assertFalse($this->policy->create($student, $thread));
    }

    // No.26 返信投稿者本人が返信を更新
    public function test_reply_author_can_update_own_reply(): void
    {
        $author = User::factory()->student()->inProgress()->create();
        $reply = $this->createReply($author);

        $this->assertTrue($this->policy->update($author, $reply));
    }

    // No.27 返信投稿者本人が返信を削除
    public function test_reply_author_can_delete_own_reply(): void
    {
        $author = User::factory()->student()->inProgress()->create();
        $reply = $this->createReply($author);

        $this->assertTrue($this->policy->delete($author, $reply));
    }

    // No.28 他のユーザーが返信を更新
    public function test_other_user_cannot_update_reply(): void
    {
        $author = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $reply = $this->createReply($author);

        $this->assertFalse($this->policy->update($other, $reply));
    }

    // No.29 他のユーザーが返信を削除
    public function test_other_user_cannot_delete_reply(): void
    {
        $author = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $reply = $this->createReply($author);

        $this->assertFalse($this->policy->delete($other, $reply));
    }

    // No.30 管理者が返信を更新
    public function test_admin_cannot_update_reply(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->student()->inProgress()->create();
        $reply = $this->createReply($author);

        $this->assertFalse($this->policy->update($admin, $reply));
    }

    // No.31 管理者が他人の返信を削除
    public function test_admin_can_delete_reply_on_unpublished_thread(): void
    {
        $admin = User::factory()->admin()->create();

        $thread = $this->createThread(
            User::factory()->student()->inProgress()->create(),
            Certification::factory()->draft()->create(),
        );

        $reply = $this->createReply(
            User::factory()->student()->inProgress()->create(),
            $thread,
        );

        $this->assertTrue($this->policy->delete($admin, $reply));
    }

    private function createThread(
        ?User $owner = null,
        ?Certification $certification = null,
    ): QaThread {
        $owner ??= User::factory()->student()->inProgress()->create();
        $certification ??= Certification::factory()->published()->create();

        return QaThread::factory()
            ->for($certification)
            ->create([
                'user_id' => $owner->id,
            ]);
    }

    private function createReply(
        User $author,
        ?QaThread $thread = null,
    ): QaReply {
        $thread ??= $this->createThread();

        return QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'user_id' => $author->id,
        ]);
    }

    private function assignCoach(
        Certification $certification,
        User $coach,
    ): void {
        $admin = User::factory()->admin()->create();

        CertificationCoachAssignment::create([
            'id' => (string) Str::ulid(),
            'certification_id' => $certification->id,
            'user_id' => $coach->id,
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
        ]);
    }
}
