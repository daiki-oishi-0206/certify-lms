<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaBoard;

use App\Enums\CertificationStatus;
use App\Enums\QaThreadStatus;
use App\Enums\UserStatus;
use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaBoardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * 一覧画面に表示されるスレッドを取得する。
     */
    private function threadIsVisible($response, QaThread $thread): bool
    {
        $threads = $response->viewData('threads');

        return $threads->getCollection()->contains('id', $thread->id);
    }

    /**
     * コーチに資格を割り当てる。
     */
    private function assignCoach(
        Certification $certification,
        User $coach,
    ): void {
        $admin = User::factory()->admin()->create();

        $certification->coaches()->attach($coach->id, [
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | index
    |--------------------------------------------------------------------------
    */

    /** 1. 受講生が一覧を開くと公開資格の質問が表示される */
    public function test_student_can_view_published_threads(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($student)
            ->get(route('qa-board.index'));

        $response->assertOk()
            ->assertViewIs('qa-thread.index');

        $this->assertTrue($this->threadIsVisible($response, $thread));
    }

    /** 2. コーチには担当資格の質問だけが表示される */
    public function test_coach_can_view_only_assigned_certification_threads(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $assignedCertification = Certification::factory()->published()->create();
        $unassignedCertification = Certification::factory()->published()->create();

        $this->assignCoach($assignedCertification, $coach);

        $assignedThread = QaThread::factory()
            ->for($assignedCertification)
            ->create();

        $unassignedThread = QaThread::factory()
            ->for($unassignedCertification)
            ->create();

        $response = $this->actingAs($coach)
            ->get(route('qa-board.index'));

        $response->assertOk()
            ->assertViewIs('qa-thread.index');

        $this->assertTrue($this->threadIsVisible($response, $assignedThread));
        $this->assertFalse($this->threadIsVisible($response, $unassignedThread));
    }

    /** 3. 管理者には公開・非公開資格の質問が表示される */
    public function test_admin_can_view_published_and_unpublished_threads(): void
    {
        $admin = User::factory()->admin()->create();

        $publishedThread = QaThread::factory()->create();

        $unpublishedCertification = Certification::factory()->draft()->create();
        $unpublishedThread = QaThread::factory()
            ->for($unpublishedCertification)
            ->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.qa-board.index'));

        $response->assertOk()
            ->assertViewIs('qa-thread.index');

        $this->assertTrue($this->threadIsVisible($response, $publishedThread));
        $this->assertTrue($this->threadIsVisible($response, $unpublishedThread));
    }

    /** 4. 受講生には非公開資格の質問が表示されない */
    public function test_student_cannot_view_unpublished_threads_in_list(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certification = Certification::factory()->draft()->create();
        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $response = $this->actingAs($student)
            ->get(route('qa-board.index'));

        $response->assertOk();

        $this->assertFalse($this->threadIsVisible($response, $thread));
    }

    /** 5. コーチには担当資格であっても非公開資格の質問が表示されない */
    public function test_coach_cannot_view_unpublished_threads_in_list(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $certification = Certification::factory()->draft()->create();
        $this->assignCoach($certification, $coach);

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $response = $this->actingAs($coach)
            ->get(route('qa-board.index'));

        $response->assertOk();

        $this->assertFalse($this->threadIsVisible($response, $thread));
    }

    /** 6. 資格で絞り込むと選択した資格の質問だけが表示される */
    public function test_index_can_filter_threads_by_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $certificationA = Certification::factory()->published()->create();
        $certificationB = Certification::factory()->published()->create();

        $threadA = QaThread::factory()->for($certificationA)->create();
        $threadB = QaThread::factory()->for($certificationB)->create();

        $response = $this->actingAs($student)
            ->get(route('qa-board.index', [
                'certification_id' => $certificationA->id,
            ]));

        $response->assertOk();

        $this->assertTrue($this->threadIsVisible($response, $threadA));
        $this->assertFalse($this->threadIsVisible($response, $threadB));
    }

    /** 7. 解決済で絞り込むと解決済の質問だけが表示される */
    public function test_index_can_filter_resolved_threads(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $resolvedThread = QaThread::factory()->resolved()->create();
        $unresolvedThread = QaThread::factory()->create();

        $response = $this->actingAs($student)
            ->get(route('qa-board.index', [
                'status' => QaThreadStatus::Resolved->value,
            ]));

        $response->assertOk();

        $this->assertTrue($this->threadIsVisible($response, $resolvedThread));
        $this->assertFalse($this->threadIsVisible($response, $unresolvedThread));
    }

    /** 8. 未解決で絞り込むと未解決の質問だけが表示される */
    public function test_index_can_filter_unresolved_threads(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $resolvedThread = QaThread::factory()->resolved()->create();
        $unresolvedThread = QaThread::factory()->create();

        $response = $this->actingAs($student)
            ->get(route('qa-board.index', [
                'status' => QaThreadStatus::Unresolved->value,
            ]));

        $response->assertOk();

        $this->assertTrue($this->threadIsVisible($response, $unresolvedThread));
        $this->assertFalse($this->threadIsVisible($response, $resolvedThread));
    }

    /** 9. タイトルに含まれるキーワードで検索できる */
    public function test_index_can_search_keyword_in_title(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $matchedThread = QaThread::factory()->create([
            'title' => 'Laravelの認証について',
        ]);

        $unmatchedThread = QaThread::factory()->create([
            'title' => 'データベース設計について',
        ]);

        $response = $this->actingAs($student)
            ->get(route('qa-board.index', [
                'keyword' => 'Laravel',
            ]));

        $response->assertOk();

        $this->assertTrue($this->threadIsVisible($response, $matchedThread));
        $this->assertFalse($this->threadIsVisible($response, $unmatchedThread));
    }

    /** 10. 本文に含まれるキーワードで検索できる */
    public function test_index_can_search_keyword_in_body(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $matchedThread = QaThread::factory()->create([
            'body' => 'LaravelのEloquentについて質問です。',
        ]);

        $unmatchedThread = QaThread::factory()->create([
            'body' => 'データベースの設計について質問です。',
        ]);

        $response = $this->actingAs($student)
            ->get(route('qa-board.index', [
                'keyword' => 'Eloquent',
            ]));

        $response->assertOk();

        $this->assertTrue($this->threadIsVisible($response, $matchedThread));
        $this->assertFalse($this->threadIsVisible($response, $unmatchedThread));
    }

    /** 11. 21件以上の質問がページネーションされる */
    public function test_index_paginates_threads(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        QaThread::factory()->count(21)->create();

        $response = $this->actingAs($student)
            ->get(route('qa-board.index'));

        $response->assertOk();

        $threads = $response->viewData('threads');

        $this->assertCount(20, $threads->items());
        $this->assertEquals(2, $threads->lastPage());
    }

    /*
    |--------------------------------------------------------------------------
    | show
    |--------------------------------------------------------------------------
    */

    /** 12. 受講生が質問詳細を開くと本文と回答が表示される */
    public function test_student_can_view_thread_detail_and_replies(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $thread = QaThread::factory()->create([
            'title' => '質問タイトル',
            'body' => '質問本文',
        ]);

        $reply = QaReply::factory()
            ->for($thread, 'thread')
            ->create([
                'body' => '回答本文',
            ]);

        // ユーザーのステータスを確認
        $this->assertSame(
            UserStatus::InProgress,
            $student->fresh()->status
        );

        // 資格の公開状態を確認
        $this->assertSame(
            CertificationStatus::Published,
            $thread->fresh()->certification->status
        );

        // Policyによる閲覧許可を確認
        $this->assertTrue(
            $student->can('view', $thread)
        );

        $response = $this->actingAs($student)
            ->get(route('qa-board.show', $thread));

        $response->assertOk()
            ->assertViewIs('qa-thread.show')
            ->assertViewHas('thread', fn($value) => $value->id === $thread->id)
            ->assertViewHas('replies', fn($replies) => $replies->contains('id', $reply->id));
    }

    /*
    |--------------------------------------------------------------------------
    | create
    |--------------------------------------------------------------------------
    */

    /** 13. 受講生の質問作成画面には公開資格が表示される */
    public function test_student_can_view_create_page_with_published_certifications(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $publishedCertification = Certification::factory()->published()->create();
        Certification::factory()->draft()->create();

        $response = $this->actingAs($student)
            ->get(route('qa-board.create'));

        $response->assertOk()
            ->assertViewIs('qa-thread.create')
            ->assertViewHas(
                'certifications',
                fn($certifications) => $certifications->contains('id', $publishedCertification->id)
                    && $certifications->count() === 1,
            );
    }

    /*
    |--------------------------------------------------------------------------
    | store
    |--------------------------------------------------------------------------
    */

    /** 14. 受講生が質問を投稿できる */
    public function test_student_can_store_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();

        $response = $this->actingAs($student)
            ->post(route('qa-board.store'), [
                'certification_id' => $certification->id,
                'title' => '質問タイトル',
                'body' => '質問本文',
            ]);

        $thread = QaThread::query()
            ->where('title', '質問タイトル')
            ->firstOrFail();

        $response->assertRedirect(route('qa-board.show', $thread))
            ->assertSessionHas('success', '質問を投稿しました。');

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'user_id' => $student->id,
            'certification_id' => $certification->id,
            'title' => '質問タイトル',
            'body' => '質問本文',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | edit
    |--------------------------------------------------------------------------
    */

    /** 15. 受講生が自分の質問の編集画面を開ける */
    public function test_student_can_view_own_thread_edit_page(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->create([
                'title' => '編集前タイトル',
                'body' => '編集前本文',
            ]);

        $response = $this->actingAs($student)
            ->get(route('qa-board.edit', $thread));

        $response->assertOk()
            ->assertViewIs('qa-thread.edit')
            ->assertViewHas('thread', fn($value) => $value->id === $thread->id);
    }

    /*
    |--------------------------------------------------------------------------
    | update
    |--------------------------------------------------------------------------
    */

    /** 16. 受講生が自分の質問を更新できる */
    public function test_student_can_update_own_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->create();

        $response = $this->actingAs($student)
            ->put(route('qa-board.update', $thread), [
                'title' => '更新後タイトル',
                'body' => '更新後本文',
            ]);

        $response->assertRedirect(route('qa-board.show', $thread))
            ->assertSessionHas('success', '質問を更新しました。');

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'title' => '更新後タイトル',
            'body' => '更新後本文',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | updateStatus
    |--------------------------------------------------------------------------
    */

    /** 17. 受講生が自分の質問を解決済に変更できる */
    public function test_student_can_mark_own_thread_as_resolved(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->create([
                'status' => QaThreadStatus::Unresolved,
                'resolved_at' => null,
            ]);

        $response = $this->actingAs($student)
            ->patch(route('qa-board.updateStatus', $thread), [
                'status' => QaThreadStatus::Resolved->value,
            ]);

        $response->assertRedirect(route('qa-board.show', $thread))
            ->assertSessionHas('success', '質問を解決済にマークしました。');

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Resolved->value,
        ]);

        $this->assertNotNull($thread->fresh()->resolved_at);
    }

    /** 18. 受講生が自分の質問を未解決に戻せる */
    public function test_student_can_mark_own_thread_as_unresolved(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->resolved()
            ->create();

        $response = $this->actingAs($student)
            ->patch(route('qa-board.updateStatus', $thread), [
                'status' => QaThreadStatus::Unresolved->value,
            ]);

        $response->assertRedirect(route('qa-board.show', $thread))
            ->assertSessionHas('success', '質問を未解決に戻しました。');

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Unresolved->value,
            'resolved_at' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | destroy
    |--------------------------------------------------------------------------
    */

    /** 19. 受講生が回答のない自分の質問を削除できる */
    public function test_student_can_delete_own_thread_without_replies(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->create();

        $response = $this->actingAs($student)
            ->delete(route('qa-board.destroy', $thread));

        $response->assertRedirect(route('qa-board.index'))
            ->assertSessionHas('success', '質問を削除しました。');

        $this->assertDatabaseMissing('qa_threads', [
            'id' => $thread->id,
        ]);
    }

    /** 20. 管理者が回答のある質問を削除できる */
    public function test_admin_can_delete_thread_with_replies(): void
    {
        $admin = User::factory()->admin()->create();

        $thread = QaThread::factory()->create();
        QaReply::factory()->for($thread, 'thread')->create();

        $response = $this->actingAs($admin)
            ->delete(route('admin.qa-board.destroy', $thread));

        $response->assertRedirect(route('admin.qa-board.index'))
            ->assertSessionHas('success', '質問を削除しました。');

        $this->assertDatabaseMissing('qa_threads', [
            'id' => $thread->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | storeReply
    |--------------------------------------------------------------------------
    */

    /** 21. 受講生が他の受講生の質問に回答できる */
    public function test_student_can_reply_to_another_students_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($student)
            ->post(route('qa-board.replies.store', $thread), [
                'body' => '回答本文',
            ]);

        $response->assertRedirect(route('qa-board.show', $thread))
            ->assertSessionHas('success', '回答を投稿しました。');

        $this->assertDatabaseHas('qa_replies', [
            'qa_thread_id' => $thread->id,
            'user_id' => $student->id,
            'body' => '回答本文',
        ]);
    }

    /** 22. 受講生が自分の質問に回答できる */
    public function test_student_can_reply_to_own_thread(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $thread = QaThread::factory()
            ->for($student, 'user')
            ->create();

        $response = $this->actingAs($student)
            ->post(route('qa-board.replies.store', $thread), [
                'body' => '自分の質問への回答',
            ]);

        $response->assertRedirect(route('qa-board.show', $thread));

        $this->assertDatabaseHas('qa_replies', [
            'qa_thread_id' => $thread->id,
            'user_id' => $student->id,
            'body' => '自分の質問への回答',
        ]);
    }

    /** 23. コーチが担当資格の質問に回答できる */
    public function test_assigned_coach_can_reply_to_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $certification = Certification::factory()->published()->create();
        $this->assignCoach($certification, $coach);

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $response = $this->actingAs($coach)
            ->post(route('qa-board.replies.store', $thread), [
                'body' => 'コーチからの回答',
            ]);

        $response->assertRedirect(route('qa-board.show', $thread));

        $this->assertDatabaseHas('qa_replies', [
            'qa_thread_id' => $thread->id,
            'user_id' => $coach->id,
            'body' => 'コーチからの回答',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | editReply
    |--------------------------------------------------------------------------
    */

    /** 24. 受講生が自分の回答の編集画面を開ける */
    public function test_student_can_view_own_reply_edit_page(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $reply = QaReply::factory()
            ->for($student, 'user')
            ->create([
                'body' => '編集前の回答',
            ]);

        $response = $this->actingAs($student)
            ->get(route('qa-board.replies.edit', $reply));

        $response->assertOk()
            ->assertViewIs('qa-thread.reply-edit')
            ->assertViewHas('reply', fn($value) => $value->id === $reply->id);
    }

    /** 25. コーチが自分の回答の編集画面を開ける */
    public function test_coach_can_view_own_reply_edit_page(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $certification = Certification::factory()->published()->create();
        $this->assignCoach($certification, $coach);

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $reply = QaReply::factory()
            ->for($thread, 'thread')
            ->for($coach, 'user')
            ->create([
                'body' => '編集前の回答',
            ]);

        $response = $this->actingAs($coach)
            ->get(route('qa-board.replies.edit', $reply));

        $response->assertOk()
            ->assertViewIs('qa-thread.reply-edit')
            ->assertViewHas('reply', fn($value) => $value->id === $reply->id);
    }

    /*
    |--------------------------------------------------------------------------
    | updateReply
    |--------------------------------------------------------------------------
    */

    /** 26. 受講生が自分の回答を更新できる */
    public function test_student_can_update_own_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $reply = QaReply::factory()
            ->for($student, 'user')
            ->create();

        $thread = $reply->thread;

        $response = $this->actingAs($student)
            ->patch(route('qa-board.replies.update', $reply), [
                'body' => '更新後の回答',
            ]);

        $response->assertRedirect(route('qa-board.show', $thread))
            ->assertSessionHas('success', '回答を更新しました。');

        $this->assertDatabaseHas('qa_replies', [
            'id' => $reply->id,
            'body' => '更新後の回答',
        ]);
    }

    /** 27. コーチが自分の回答を更新できる */
    public function test_coach_can_update_own_reply(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $certification = Certification::factory()->published()->create();
        $this->assignCoach($certification, $coach);

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $reply = QaReply::factory()
            ->for($thread, 'thread')
            ->for($coach, 'user')
            ->create();

        $response = $this->actingAs($coach)
            ->patch(route('qa-board.replies.update', $reply), [
                'body' => '更新後の回答',
            ]);

        $response->assertRedirect(route('qa-board.show', $thread))
            ->assertSessionHas('success', '回答を更新しました。');

        $this->assertDatabaseHas('qa_replies', [
            'id' => $reply->id,
            'body' => '更新後の回答',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | destroyReply
    |--------------------------------------------------------------------------
    */

    /** 28. 受講生が自分の回答を削除できる */
    public function test_student_can_delete_own_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();

        $reply = QaReply::factory()
            ->for($student, 'user')
            ->create();

        $thread = $reply->thread;

        $response = $this->actingAs($student)
            ->delete(route('qa-board.replies.destroy', $reply));

        $response->assertRedirect(route('qa-board.show', $thread))
            ->assertSessionHas('success', '回答を削除しました。');

        $this->assertDatabaseMissing('qa_replies', [
            'id' => $reply->id,
        ]);
    }

    /** 29. コーチが自分の回答を削除できる */
    public function test_coach_can_delete_own_reply(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $certification = Certification::factory()->published()->create();
        $this->assignCoach($certification, $coach);

        $thread = QaThread::factory()
            ->for($certification)
            ->create();

        $reply = QaReply::factory()
            ->for($thread, 'thread')
            ->for($coach, 'user')
            ->create();

        $response = $this->actingAs($coach)
            ->delete(route('qa-board.replies.destroy', $reply));

        $response->assertRedirect(route('qa-board.show', $thread))
            ->assertSessionHas('success', '回答を削除しました。');

        $this->assertDatabaseMissing('qa_replies', [
            'id' => $reply->id,
        ]);
    }

    /** 30. 管理者が他のユーザーの回答を削除できる */
    public function test_admin_can_delete_another_users_reply(): void
    {
        $admin = User::factory()->admin()->create();

        $reply = QaReply::factory()->create();
        $thread = $reply->thread;

        $response = $this->actingAs($admin)
            ->delete(route('admin.qa-board.replies.destroy', $reply));

        $response->assertRedirect(route('admin.qa-board.show', $thread))
            ->assertSessionHas('success', '回答を削除しました。');

        $this->assertDatabaseMissing('qa_replies', [
            'id' => $reply->id,
        ]);
    }
}
