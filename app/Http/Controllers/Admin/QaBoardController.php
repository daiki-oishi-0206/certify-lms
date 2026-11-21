<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CertificationStatus;
use App\Enums\QaThreadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\QaBoard\IndexThreadRequest;
use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\UseCases\QaBoard\DeleteAction;
use App\UseCases\QaBoard\DeleteReplyAction;
use App\UseCases\QaBoard\IndexAction;
use App\UseCases\QaBoard\ShowAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QaBoardController extends Controller
{
    public function index(
        IndexThreadRequest $request,
        IndexAction $action,
    ): View {
        $filters = $request->filters();

        $status = $filters['status'] !== null
            ? QaThreadStatus::from($filters['status'])
            : null;

        $threads = $action(
            $request->user(),
            $filters['keyword'],
            $filters['certification_id'],
            $status,
        );

        $certifications = Certification::query()
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
        $thread = $action($thread);

        return view('qa-thread.show', [
            'thread' => $thread,
            'replies' => $thread->replies,
        ]);
    }

    public function destroy(
        QaThread $thread,
        DeleteAction $action,
    ): RedirectResponse {
        $action($thread);

        return redirect()
            ->route('admin.qa-board.index')
            ->with('success', '質問を削除しました。');
    }

    public function destroyReply(
        QaReply $reply,
        DeleteReplyAction $action,
    ): RedirectResponse {
        $thread = $reply->qaThread;

        $action($reply);

        return redirect()
            ->route('admin.qa-board.show', $thread)
            ->with('success', '回答を削除しました。');
    }
}
