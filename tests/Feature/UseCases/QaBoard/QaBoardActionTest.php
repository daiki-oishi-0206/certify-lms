<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaBoard;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use App\UseCases\QaBoard\DeleteAction;
use App\UseCases\QaBoard\DeleteReplyAction;
use App\UseCases\QaBoard\IndexAction;
use App\UseCases\QaBoard\ShowAction;
use App\UseCases\QaBoard\StoreAction;
use App\UseCases\QaBoard\StoreReplyAction;
use App\UseCases\QaBoard\UpdateAction;
use App\UseCases\QaBoard\UpdateReplyAction;
use App\UseCases\QaBoard\UpdateStatusAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaBoardActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. 正常なスレッド作成
     */
    public function test_store_action_creates_thread(): void
    {
        $user = User::factory()->student()->create();
        $certification = Certification::factory()->published()->create();

        $action = new StoreAction();

        $thread = $action(
            $user,
            (string) $certification->id,
            'テストタイトル',
            'テスト本文',
        );

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'user_id' => $user->id,
            'certification_id' => $certification->id,
            'title' => 'テストタイトル',
            'body' => 'テスト本文',
        ]);
    }

    /**
     * 2. 正常なスレッド更新
     */
    public function test_update_action_updates_thread(): void
    {
        $thread = QaThread::factory()->create();

        $action = new UpdateAction();

        $updatedThread = $action(
            $thread,
            '更新後のタイトル',
            '更新後の本文',
        );

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'title' => '更新後のタイトル',
            'body' => '更新後の本文',
        ]);

        $this->assertSame($thread->id, $updatedThread->id);
    }

    /**
     * 3. 正常なスレッド削除
     */
    public function test_delete_action_deletes_thread(): void
    {
        $thread = QaThread::factory()->create();

        $action = new DeleteAction();

        $action($thread);

        $this->assertDatabaseMissing('qa_threads', [
            'id' => $thread->id,
        ]);
    }

    /**
     * 4. 未解決スレッドを解決済みに変更
     */
    public function test_update_status_action_resolves_thread(): void
    {
        $thread = QaThread::factory()->create([
            'status' => QaThreadStatus::Unresolved,
            'resolved_at' => null,
        ]);

        $action = new UpdateStatusAction();

        $updatedThread = $action(
            $thread,
            QaThreadStatus::Resolved,
        );

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Resolved->value,
        ]);

        $this->assertNotNull($updatedThread->resolved_at);
    }

    /**
     * 5. 解決済みスレッドを未解決に変更
     */
    public function test_update_status_action_unresolves_thread(): void
    {
        $thread = QaThread::factory()->resolved()->create();

        $action = new UpdateStatusAction();

        $updatedThread = $action(
            $thread,
            QaThreadStatus::Unresolved,
        );

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Unresolved->value,
            'resolved_at' => null,
        ]);

        $this->assertNull($updatedThread->resolved_at);
    }

    /**
     * 6. 正常な返信投稿
     */
    public function test_store_reply_action_creates_reply(): void
    {
        $thread = QaThread::factory()->create();
        $user = User::factory()->student()->create();

        $action = new StoreReplyAction();

        $reply = $action(
            $thread,
            $user,
            'テスト返信本文',
        );

        $this->assertDatabaseHas('qa_replies', [
            'id' => $reply->id,
            'qa_thread_id' => $thread->id,
            'user_id' => $user->id,
            'body' => 'テスト返信本文',
        ]);
    }

    /**
     * 7. 正常な返信更新
     */
    public function test_update_reply_action_updates_reply(): void
    {
        $reply = QaReply::factory()->create();

        $action = new UpdateReplyAction();

        $updatedReply = $action(
            $reply,
            '更新後の返信本文',
        );

        $this->assertDatabaseHas('qa_replies', [
            'id' => $reply->id,
            'body' => '更新後の返信本文',
        ]);

        $this->assertSame($reply->id, $updatedReply->id);
    }

    /**
     * 8. 正常な返信削除
     */
    public function test_delete_reply_action_deletes_reply(): void
    {
        $reply = QaReply::factory()->create();

        $action = new DeleteReplyAction();

        $action($reply);

        $this->assertDatabaseMissing('qa_replies', [
            'id' => $reply->id,
        ]);
    }

    /**
     * 9. スレッド詳細取得
     */
    public function test_show_action_loads_thread_and_replies(): void
    {
        $thread = QaThread::factory()->create();

        $firstReplyUser = User::factory()->student()->create();
        $secondReplyUser = User::factory()->coach()->create();

        $firstReply = QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'user_id' => $firstReplyUser->id,
            'created_at' => now()->subMinutes(2),
        ]);

        $secondReply = QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'user_id' => $secondReplyUser->id,
            'created_at' => now()->subMinute(),
        ]);

        $action = new ShowAction();

        $result = $action($thread);

        $this->assertTrue($result->relationLoaded('user'));
        $this->assertTrue($result->relationLoaded('certification'));
        $this->assertTrue($result->relationLoaded('replies'));

        $this->assertSame($thread->id, $result->id);
        $this->assertSame(
            $thread->user_id,
            $result->user->id,
        );
        $this->assertSame(
            $thread->certification_id,
            $result->certification->id,
        );

        $this->assertCount(2, $result->replies);

        $this->assertSame(
            [$firstReply->id, $secondReply->id],
            $result->replies->pluck('id')->all(),
        );

        $this->assertTrue(
            $result->replies->every(
                fn(QaReply $reply): bool => $reply->relationLoaded('user'),
            ),
        );
    }

    /**
     * 10. スレッド一覧取得
     */
    public function test_index_action_returns_threads_in_newest_order(): void
    {
        $viewer = User::factory()->student()->create();

        $firstThread = QaThread::factory()->create([
            'created_at' => now()->subMinutes(2),
        ]);

        $secondThread = QaThread::factory()->create([
            'created_at' => now()->subMinute(),
        ]);

        $action = new IndexAction();

        $result = $action(
            $viewer,
            null,
            null,
            null,
        );

        $this->assertSame(
            [$secondThread->id, $firstThread->id],
            array_map(
                fn(QaThread $thread): string => $thread->id,
                $result->items(),
            ),
        );

        $this->assertTrue(
            collect($result->items())->every(
                fn(QaThread $thread): bool =>
                $thread->relationLoaded('user')
                    && $thread->relationLoaded('certification'),
            ),
        );
    }

    /**
     * 11. 資格による絞り込み
     */
    public function test_index_action_filters_by_certification(): void
    {
        $viewer = User::factory()->student()->create();

        $targetCertification = Certification::factory()->published()->create();
        $otherCertification = Certification::factory()->published()->create();

        $targetThread = QaThread::factory()->create([
            'certification_id' => $targetCertification->id,
        ]);

        QaThread::factory()->create([
            'certification_id' => $otherCertification->id,
        ]);

        $action = new IndexAction();

        $result = $action(
            $viewer,
            null,
            (string) $targetCertification->id,
            null,
        );

        $this->assertSame(
            [$targetThread->id],
            array_map(
                fn(QaThread $thread): string => $thread->id,
                $result->items(),
            ),
        );
    }

    /**
     * 12. ステータスによる絞り込み
     */
    public function test_index_action_filters_by_status(): void
    {
        $viewer = User::factory()->student()->create();

        $resolvedThread = QaThread::factory()->resolved()->create();

        QaThread::factory()->create([
            'status' => QaThreadStatus::Unresolved,
            'resolved_at' => null,
        ]);

        $action = new IndexAction();

        $result = $action(
            $viewer,
            null,
            null,
            QaThreadStatus::Resolved,
        );

        $this->assertSame(
            [$resolvedThread->id],
            array_map(
                fn(QaThread $thread): string => $thread->id,
                $result->items(),
            ),
        );
    }

    /**
     * 13. タイトルによるキーワード絞り込み
     */
    public function test_index_action_filters_by_title_keyword(): void
    {
        $viewer = User::factory()->student()->create();

        $targetThread = QaThread::factory()->create([
            'title' => 'Laravelの学習方法',
            'body' => 'フレームワークについての質問です。',
        ]);

        QaThread::factory()->create([
            'title' => 'PHPの質問',
            'body' => 'Laravelについて知りたいです。',
        ]);

        $action = new IndexAction();

        $result = $action(
            $viewer,
            'Laravelの学習方法',
            null,
            null,
        );

        $this->assertSame(
            [$targetThread->id],
            array_map(
                fn(QaThread $thread): string => $thread->id,
                $result->items(),
            ),
        );
    }

    /**
     * 14. 本文によるキーワード絞り込み
     */
    public function test_index_action_filters_by_body_keyword(): void
    {
        $viewer = User::factory()->student()->create();

        $targetThread = QaThread::factory()->create([
            'title' => 'PHPの質問',
            'body' => 'Laravelのルーティングについて知りたいです。',
        ]);

        QaThread::factory()->create([
            'title' => 'Laravelの質問',
            'body' => 'データベースについて知りたいです。',
        ]);

        $action = new IndexAction();

        $result = $action(
            $viewer,
            'Laravelのルーティング',
            null,
            null,
        );

        $this->assertSame(
            [$targetThread->id],
            array_map(
                fn(QaThread $thread): string => $thread->id,
                $result->items(),
            ),
        );
    }

    /**
     * 15. ページネーション
     */
    public function test_index_action_paginates_threads(): void
    {
        $viewer = User::factory()->student()->create();

        QaThread::factory()->count(25)->create();

        $action = new IndexAction();

        $result = $action(
            $viewer,
            null,
            null,
            null,
            10,
        );

        $this->assertSame(25, $result->total());
        $this->assertSame(10, $result->perPage());
        $this->assertSame(1, $result->currentPage());
        $this->assertCount(10, $result->items());
        $this->assertSame(3, $result->lastPage());
    }
}
