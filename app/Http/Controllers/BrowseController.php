<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Enrollment;
use App\Models\Part;
use App\Models\Section;
use App\Models\User;
use App\UseCases\Learning\IndexAction;
use App\UseCases\Learning\ShowChapterAction;
use App\UseCases\Learning\ShowEnrollmentAction;
use App\UseCases\Learning\ShowPartAction;
use App\UseCases\Learning\ShowSectionAction;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 受講生向け教材ブラウジング Controller。
 * /learning(empty-state)→/learning/enrollments/{enrollment}→/learning/parts/{part}→
 * /learning/chapters/{chapter}→/learning/sections/{section} の 5 階層動線を提供する。
 *
 * Section 閲覧時の LearningSession 自動開始は StartLearningSession ミドルウェアが、
 * AI 相談ウィジェット向けの Section 文脈 (pageMeta) は SectionPageMetaComposer が担う。
 */
class BrowseController extends Controller
{
    public function index(IndexAction $action): View
    {
        return view('learning.index', $action(auth()->user()));
    }

    public function showEnrollment(Enrollment $enrollment, Request $request, ShowEnrollmentAction $action): View
    {
        $this->authorize('view', $enrollment);

        $tab = $request->query('tab') === 'quizzes' ? 'quizzes' : 'contents';

        return view('learning.enrollments.show', $action($enrollment, $tab));
    }

    public function showPart(Part $part, ShowPartAction $action): View
    {
        $this->authorizeLearningContent(auth()->user(), $part->certification_id);

        return view('learning.parts.show', $action($part, auth()->user()));
    }

    public function showChapter(Chapter $chapter, ShowChapterAction $action): View
    {
        $chapter->loadMissing('part');

        $this->authorizeLearningContent(
            auth()->user(),
            $chapter->part?->certification_id
        );

        return view('learning.chapters.show', $action($chapter, auth()->user()));
    }

    public function showSection(Section $section, ShowSectionAction $action): View
    {
        $section->loadMissing('chapter.part');
        
        $this->authorizeLearningContent(
            auth()->user(),
            $section->chapter?->part?->certification_id
        );

        return view('learning.sections.show', $action($section, auth()->user()));
    }

    private function authorizeLearningContent(User $student, ?string $certificationId): void
    {
        abort_if($certificationId === null, 404);

        $enrollment = $student->enrollments()
            ->where('certification_id', $certificationId)
            ->first();

        abort_if($enrollment === null, 403);

        $this->authorize('viewLearningContent', $enrollment);
    }
}
