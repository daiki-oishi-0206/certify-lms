<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\MeetingPack;
use App\Models\User;
use App\Policies\MeetingPackPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MeetingPackPolicy のロール別認可テスト。
 */
class MeetingPackPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function assertAbility(
        string $role,
        string $ability,
        bool $expected,
    ): void {
        $actor = User::factory()->{$role}()->create();
        $policy = new MeetingPackPolicy;

        if (in_array($ability, ['viewAny', 'create'], true)) {
            $result = $policy->{$ability}($actor);
        } else {
            $plan = MeetingPack::factory()->create();
            $result = $policy->{$ability}($actor, $plan);
        }

        $this->assertSame($expected, $result);
    }

    // 一覧閲覧

    public function test_admin_can_view_any(): void
    {
        $this->assertAbility('admin', 'viewAny', true);
    }

    public function test_coach_cannot_view_any(): void
    {
        $this->assertAbility('coach', 'viewAny', false);
    }

    public function test_student_cannot_view_any(): void
    {
        $this->assertAbility('student', 'viewAny', false);
    }

    // 詳細閲覧

    public function test_admin_can_view_meeting_pack(): void
    {
        $this->assertAbility('admin', 'view', true);
    }

    public function test_coach_cannot_view_meeting_pack(): void
    {
        $this->assertAbility('coach', 'view', false);
    }

    public function test_student_cannot_view_meeting_pack(): void
    {
        $this->assertAbility('student', 'view', false);
    }

    // 新規作成

    public function test_admin_can_create_meeting_pack(): void
    {
        $this->assertAbility('admin', 'create', true);
    }

    public function test_coach_cannot_create_meeting_pack(): void
    {
        $this->assertAbility('coach', 'create', false);
    }

    public function test_student_cannot_create_meeting_pack(): void
    {
        $this->assertAbility('student', 'create', false);
    }

    // 更新

    public function test_admin_can_update_meeting_pack(): void
    {
        $this->assertAbility('admin', 'update', true);
    }

    public function test_coach_cannot_update_meeting_pack(): void
    {
        $this->assertAbility('coach', 'update', false);
    }

    public function test_student_cannot_update_meeting_pack(): void
    {
        $this->assertAbility('student', 'update', false);
    }

    // 削除

    public function test_admin_can_delete_meeting_pack(): void
    {
        $this->assertAbility('admin', 'delete', true);
    }

    public function test_coach_cannot_delete_meeting_pack(): void
    {
        $this->assertAbility('coach', 'delete', false);
    }

    public function test_student_cannot_delete_meeting_pack(): void
    {
        $this->assertAbility('student', 'delete', false);
    }

    // 公開

    public function test_admin_can_publish_meeting_pack(): void
    {
        $this->assertAbility('admin', 'publish', true);
    }

    public function test_coach_cannot_publish_meeting_pack(): void
    {
        $this->assertAbility('coach', 'publish', false);
    }

    public function test_student_cannot_publish_meeting_pack(): void
    {
        $this->assertAbility('student', 'publish', false);
    }

    // アーカイブ

    public function test_admin_can_archive_meeting_pack(): void
    {
        $this->assertAbility('admin', 'archive', true);
    }

    public function test_coach_cannot_archive_meeting_pack(): void
    {
        $this->assertAbility('coach', 'archive', false);
    }

    public function test_student_cannot_archive_meeting_pack(): void
    {
        $this->assertAbility('student', 'archive', false);
    }

    // アーカイブ解除

    public function test_admin_can_unarchive_meeting_pack(): void
    {
        $this->assertAbility('admin', 'unarchive', true);
    }

    public function test_coach_cannot_unarchive_meeting_pack(): void
    {
        $this->assertAbility('coach', 'unarchive', false);
    }

    public function test_student_cannot_unarchive_meeting_pack(): void
    {
        $this->assertAbility('student', 'unarchive', false);
    }
}
