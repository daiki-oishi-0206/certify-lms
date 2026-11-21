<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\QaThreadStatus;
use App\Models\QaThread;
use App\Models\QaReply;
use App\UseCases\QaBoard\IndexAction;
use App\UseCases\QaBoard\ShowAction;
use App\UseCases\QaBoard\DeleteAction;
use App\UseCases\QaBoard\DeleteReplyAction;
use App\Http\Requests\QaBoard\IndexThreadRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class QaBoardController extends Controller
{
    /**
     * 管理者向けQ&A掲示板一覧を表示する。
     */
    public function index(IndexThreadRequest $request, IndexAction $action): View
    {
        $threads = $action(
            $request->user(),
            $request->input('keyword'),
            $request->input('certification_id'),
            $request->input('status')
                ? QaThreadStatus::from($request->input('status'))
                : null,
        );

        return view('admin.qa-board.index', [
            'threads' => $threads,
        ]);
    }

    /**
     * 管理者向けQ&Aスレッド詳細を表示する。
     */
    public function show(QaThread $thread, ShowAction $action): View
    {
        $thread = $action($thread);

        return view('admin.qa-board.show', [
            'thread' => $thread,
            'replies' => $thread->replies,
        ]);
    }

    /**
     * 管理者がQ&Aスレッドを削除する。
     */
    public function destroy(QaThread $thread, DeleteAction $action): RedirectResponse
    {
        $action($thread);

        return redirect()
            ->route('admin.qa-board.index')
            ->with('success', '質問を削除しました。');
    }

    /**
     * 管理者がQ&A返信を削除する。
     */
    public function destroyReply(
        QaReply $reply,
        DeleteReplyAction $action
    ): RedirectResponse {
        $thread = $reply->qaThread;

        $action($reply);

        return redirect()
            ->route('admin.qa-board.show', $thread)
            ->with('success', '回答を削除しました。');
    }
}
