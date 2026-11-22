<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\QaThreadStatus;
use App\Enums\CertificationStatus;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\QaBoard\IndexThreadRequest;
use App\Http\Requests\QaBoard\StoreThreadRequest;
use App\Http\Requests\QaBoard\UpdateThreadRequest;
use App\Http\Requests\QaBoard\UpdateThreadStatusRequest;
use App\Http\Requests\QaBoard\StoreReplyRequest;
use App\Http\Requests\QaBoard\UpdateReplyRequest;
use App\Models\QaThread;
use App\Models\Certification;
use App\Models\QaReply;
use App\UseCases\QaBoard\IndexAction;
use App\UseCases\QaBoard\ShowAction;
use App\UseCases\QaBoard\StoreAction;
use App\UseCases\QaBoard\UpdateAction;
use App\UseCases\QaBoard\UpdateStatusAction;
use App\UseCases\QaBoard\DeleteAction;
use App\UseCases\QaBoard\StoreReplyAction;
use App\UseCases\QaBoard\UpdateReplyAction;
use App\UseCases\QaBoard\DeleteReplyAction;
use Illuminate\View\View;

/**
 * Q&Aボードコントローラー。
 *
 * 受講生・コーチが利用するQ&Aボードの処理を担当する。
 */
class QaBoardController extends Controller
{
    public function index(
        IndexThreadRequest $request,
        IndexAction $action,
    ): View {
        $viewer = $request->user();
        $filters = $request->filters();

        $status = $filters['status'] !== null
            ? QaThreadStatus::from($filters['status'])
            : null;

        $threads = $action(
            $viewer,
            $filters['keyword'],
            $filters['certification_id'],
            $status,
        );

        $certifications = Certification::query()
            ->where('status', CertificationStatus::Published)
            ->orderBy('name')
            ->get();

        return view('qa-thread.index', [
            'threads' => $threads,
            'filters' => $filters,
            'certifications' => $certifications,
            'publishedStatus' => CertificationStatus::Published,
        ]);
    }

    public function show(
        QaThread $thread,
        ShowAction $action,
    ): View {
        $this->authorize('view', $thread);

        $thread = $action($thread);

        return view('qa-thread.show', [
            'thread' => $thread,
            'replies' => $thread->replies,
        ]);
    }

    public function create(): View
    {
        $certifications = Certification::query()
            ->where('status', CertificationStatus::Published)
            ->orderBy('name')
            ->get();

        return view('qa-thread.create', [
            'certifications' => $certifications,
        ]);
    }

    public function store(
        StoreThreadRequest $request,
        StoreAction $action,
    ): RedirectResponse {
        $data = $request->validated();

        $certification = Certification::findOrFail($data['certification_id']);

        $this->authorize('create', [QaThread::class, $certification]);

        $thread = $action(
            $request->user(),
            $data['certification_id'],
            $data['title'],
            $data['body'],
        );

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を投稿しました。');
    }

    public function edit(QaThread $thread): View
    {
        $this->authorize('update', $thread);

        return view('qa-thread.edit', [
            'thread' => $thread,
        ]);
    }

    public function update(
        QaThread $thread,
        UpdateThreadRequest $request,
        UpdateAction $action,
    ): RedirectResponse {
        $data = $request->validated();

        $action(
            $thread,
            $data['title'],
            $data['body'],
        );

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を更新しました。');
    }

    public function updateStatus(
        QaThread $thread,
        UpdateThreadStatusRequest $request,
        UpdateStatusAction $action,
    ): RedirectResponse {
        $data = $request->validated();

        $status = QaThreadStatus::from($data['status']);

        $this->authorize('resolve', $thread);

        $action($thread, $status);

        $message = $status === QaThreadStatus::Resolved
            ? '質問を解決済にマークしました。'
            : '質問を未解決に戻しました。';

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', $message);
    }

    public function destroy(
        QaThread $thread,
        DeleteAction $action,
    ): RedirectResponse {
        $this->authorize('delete', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問を削除しました。');
    }

    public function storeReply(
        QaThread $thread,
        StoreReplyRequest $request,
        StoreReplyAction $action,
    ): RedirectResponse {
        $data = $request->validated();

        $action(
            $thread,
            $request->user(),
            $data['body'],
        );

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '回答を投稿しました。');
    }

    public function editReply(QaReply $reply): View
    {
        $this->authorize('update', $reply);

        return view('qa-thread.reply-edit', [
            'reply' => $reply,
        ]);
    }

    public function updateReply(
        QaReply $reply,
        UpdateReplyRequest $request,
        UpdateReplyAction $action,
    ): RedirectResponse {
        $data = $request->validated();

        $action($reply, $data['body']);

        return redirect()
            ->route('qa-board.show', $reply->qaThread)
            ->with('success', '回答を更新しました。');
    }

    public function destroyReply(
        QaReply $reply,
        DeleteReplyAction $action,
    ): RedirectResponse {
        $this->authorize('delete', $reply);

        $thread = $reply->qaThread;

        $action($reply);

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '回答を削除しました。');
    }
}


