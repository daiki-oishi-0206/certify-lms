<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Certification;
use App\Models\CertificationCoachAssignment;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use App\Policies\QaThreadPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QaThreadPolicyTest extends TestCase
{
    use RefreshDatabase;

    private QaThreadPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new QaThreadPolicy;
    }

    // No.1 受講生が公開資格のスレッドを閲覧
    public function test_student_can_view_published_certification_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = $this->createThread($student, $certification);

        $this->assertTrue($this->policy->view($student, $thread));
    }

    // No.2 受講生が未公開資格のスレッドを閲覧
    public function test_student_cannot_view_unpublished_certification_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->draft()->create();
        $thread = $this->createThread($student, $certification);

        $this->assertFalse($this->policy->view($student, $thread));
    }

    // No.3 受講生が未登録資格のスレッドを閲覧
    public function test_student_can_view_unregistered_published_certification_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = $this->createThread($student, $certification);

        $this->assertTrue($this->policy->view($student, $thread));
    }

    // No.4 担当コーチが担当資格のスレッドを閲覧
    public function test_assigned_coach_can_view_assigned_certification_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->published()->create();

        $this->assignCoach($certification, $coach);

        $thread = $this->createThread(
            User::factory()->student()->inProgress()->create(),
            $certification,
        );

        $this->assertTrue($this->policy->view($coach, $thread));
    }

    // No.5 コーチが担当外資格のスレッドを閲覧
    public function test_coach_cannot_view_unassigned_certification_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->published()->create();

        $thread = $this->createThread(
            User::factory()->student()->inProgress()->create(),
            $certification,
        );

        $this->assertFalse($this->policy->view($coach, $thread));
    }

    // No.6 管理者が未公開資格のスレッドを閲覧
    public function test_admin_can_view_unpublished_certification_thread(): void
    {
        $admin = User::factory()->admin()->create();
        $certification = Certification::factory()->draft()->create();

        $thread = $this->createThread(
            User::factory()->student()->inProgress()->create(),
            $certification,
        );

        $this->assertTrue($this->policy->view($admin, $thread));
    }

    // No.7 投稿者本人がスレッドを更新
    public function test_thread_owner_can_update_own_thread(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $thread = $this->createThread($owner);

        $this->assertTrue($this->policy->update($owner, $thread));
    }

    // No.8 投稿者本人が返信のないスレッドを削除
    public function test_thread_owner_can_delete_own_thread_without_replies(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $thread = $this->createThread($owner);

        $this->assertTrue($this->policy->delete($owner, $thread));
    }

    // No.9 投稿者本人が返信のあるスレッドを削除
    public function test_thread_owner_cannot_delete_own_thread_with_replies(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $thread = $this->createThread($owner);

        QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
        ]);

        $this->assertFalse($this->policy->delete($owner, $thread));
    }

    // No.10 他の受講生がスレッドを更新
    public function test_other_student_cannot_update_thread(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $thread = $this->createThread($owner);

        $this->assertFalse($this->policy->update($other, $thread));
    }

    // No.11 他の受講生がスレッドを削除
    public function test_other_student_cannot_delete_thread(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $thread = $this->createThread($owner);

        $this->assertFalse($this->policy->delete($other, $thread));
    }

    // No.12 管理者が他人のスレッドを削除
    public function test_admin_can_delete_another_users_thread_with_replies(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = $this->createThread(
            User::factory()->student()->inProgress()->create(),
        );

        QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
        ]);

        $this->assertTrue($this->policy->delete($admin, $thread));
    }

    // No.13 管理者がスレッドを更新
    public function test_admin_cannot_update_another_users_thread(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = $this->createThread(
            User::factory()->student()->inProgress()->create(),
        );

        $this->assertFalse($this->policy->update($admin, $thread));
    }

    // No.14 管理者がスレッドの解決状態を変更
    public function test_admin_cannot_change_thread_resolution_status(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = $this->createThread(
            User::factory()->student()->inProgress()->create(),
        );

        $this->assertFalse($this->policy->resolve($admin, $thread));
        $this->assertFalse($this->policy->unresolve($admin, $thread));
    }

    // No.15 招待中の受講生はスレッドを閲覧できない
    public function test_invited_student_cannot_view_thread(): void
    {
        $student = User::factory()->student()->invited()->create();
        $certification = Certification::factory()->published()->create();
        $thread = $this->createThread($student, $certification);

        $this->assertFalse($this->policy->view($student, $thread));
    }

    // No.16 卒業済みの受講生はスレッドを閲覧できない
    public function test_graduated_student_cannot_view_thread(): void
    {
        $student = User::factory()->student()->graduated()->create();
        $certification = Certification::factory()->published()->create();
        $thread = $this->createThread($student, $certification);

        $this->assertFalse($this->policy->view($student, $thread));
    }

    // No.17 退会済みの受講生はスレッドを閲覧できない
    public function test_withdrawn_student_cannot_view_thread(): void
    {
        $student = User::factory()->student()->withdrawn()->create();
        $certification = Certification::factory()->published()->create();
        $thread = $this->createThread($student, $certification);

        $this->assertFalse($this->policy->view($student, $thread));
    }


    private function createThread(
        User $owner,
        ?Certification $certification = null,
    ): QaThread {
        $certification ??= Certification::factory()->published()->create();

        return QaThread::factory()
            ->for($certification)
            ->create([
                'user_id' => $owner->id,
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
