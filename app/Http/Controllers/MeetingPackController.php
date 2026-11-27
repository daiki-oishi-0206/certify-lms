<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MeetingPackStatus;
use App\Http\Requests\MeetingPack\IndexRequest;
use App\Http\Requests\MeetingPack\StoreRequest;
use App\Http\Requests\MeetingPack\UpdateRequest;
use App\Models\MeetingPack;
use App\Models\User;
use App\UseCases\MeetingPack\ArchiveAction;
use App\UseCases\MeetingPack\DeleteAction;
use App\UseCases\MeetingPack\IndexAction;
use App\UseCases\MeetingPack\PublishAction;
use App\UseCases\MeetingPack\ShowAction;
use App\UseCases\MeetingPack\StoreAction;
use App\UseCases\MeetingPack\UnarchiveAction;
use App\UseCases\MeetingPack\UpdateAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理者向けの面談パックマスタ管理画面 Controller。
 */
class MeetingPackController extends Controller
{
    public function index(IndexRequest $request, IndexAction $action): View
    {
        $validated = $request->validated();

        $status = isset($validated['status'])
            ? MeetingPackStatus::tryFrom($validated['status'])
            : null;

        return view('meeting-pack.management.index', [
            'plans' => $action(
                $validated['keyword'] ?? null,
                $status,
            ),
            'keyword' => $validated['keyword'] ?? '',
            'status' => $validated['status'] ?? '',
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', MeetingPack::class);

        return view('meeting-pack.management.create');
    }

    public function store(StoreRequest $request, StoreAction $action): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        $action($request->validated(), $admin);

        return redirect()
            ->route('admin.meeting-packs.index')
            ->with('success', '面談パックを登録しました。');
    }

    public function show(MeetingPack $plan, ShowAction $action): View
    {
        $this->authorize('view', $plan);

        return view('meeting-pack.management.show', [
            'plan' => $action($plan),
        ]);
    }

    public function edit(MeetingPack $plan): View
    {
        $this->authorize('update', $plan);

        return view('meeting-pack.management.edit', [
            'plan' => $plan,
        ]);
    }

    public function update(
        MeetingPack $plan,
        UpdateRequest $request,
        UpdateAction $action,
    ): RedirectResponse {
        /** @var User $admin */
        $admin = $request->user();

        $action($plan, $request->validated(), $admin);

        return redirect()
            ->route('admin.meeting-packs.show', $plan)
            ->with('success', '面談パックを更新しました。');
    }

    public function destroy(MeetingPack $plan, DeleteAction $action): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $action($plan);

        return redirect()
            ->route('admin.meeting-packs.index')
            ->with('success', '面談パックを削除しました。');
    }

    public function publish(
        MeetingPack $plan,
        Request $request,
        PublishAction $action,
    ): RedirectResponse {
        $this->authorize('publish', $plan);

        /** @var User $admin */
        $admin = $request->user();

        $action($plan, $admin);

        return redirect()
            ->route('admin.meeting-packs.show', $plan)
            ->with('success', '面談パックを公開しました。');
    }

    public function archive(
        MeetingPack $plan,
        Request $request,
        ArchiveAction $action,
    ): RedirectResponse {
        $this->authorize('archive', $plan);

        /** @var User $admin */
        $admin = $request->user();

        $action($plan, $admin);

        return redirect()
            ->route('admin.meeting-packs.show', $plan)
            ->with('success', '面談パックをアーカイブしました。');
    }

    public function unarchive(
        MeetingPack $plan,
        Request $request,
        UnarchiveAction $action,
    ): RedirectResponse {
        $this->authorize('unarchive', $plan);

        /** @var User $admin */
        $admin = $request->user();

        $action($plan, $admin);

        return redirect()
            ->route('admin.meeting-packs.show', $plan)
            ->with('success', '面談パックを下書きに戻しました。');
    }
}
