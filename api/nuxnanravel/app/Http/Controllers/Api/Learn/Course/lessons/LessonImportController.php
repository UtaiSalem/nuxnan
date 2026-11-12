<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\lessons\LessonResource;
use App\Models\Course;
use App\Services\Import\LessonImportException;
use App\Services\Import\LessonImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Create a lesson (+ topics) from a .docx file converted to HTML in the browser.
 */
class LessonImportController extends Controller
{
    public function __construct(private readonly LessonImportService $service) {}

    /**
     * A 20 MB HTML payload is held as the raw body, the decoded JSON and two DOM trees at once
     * (~90 MB measured for 13.5 MB of HTML) — more than the 128M default allows.
     */
    private function raiseMemoryLimit(): void
    {
        $current = ini_get('memory_limit');
        if ($current !== '-1' && ini_parse_quantity($current) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }
    }

    public function preview(Request $request, Course $course)
    {
        Gate::authorize('manage', $course);
        $this->raiseMemoryLimit();

        $validated = $request->validate([
            'html' => ['required', 'string', 'max:20971520'],
            'filename' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $this->service->preview($validated['html'], $validated['filename'] ?? null);
        } catch (LessonImportException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true] + $result);
    }

    public function store(Request $request, Course $course)
    {
        Gate::authorize('manage', $course);
        $this->raiseMemoryLimit();

        $validated = $request->validate([
            'import_id' => ['required', 'uuid'],
            'publication_status' => ['required', 'in:draft,published'],
            'lesson.title' => ['required', 'string', 'max:255'],
            'lesson.description' => ['nullable', 'string', 'max:5000'],
            'lesson.content' => ['nullable', 'string', 'max:16000000'],
            'lesson.youtube_url' => ['nullable', 'string', 'url:https', 'max:2000'],
            'lesson.min_read' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'topics' => ['present', 'array', 'max:10'],
            'topics.*.title' => ['required', 'string', 'max:255'],
            'topics.*.content' => ['nullable', 'string', 'max:16000000'],
            'topics.*.youtube_url' => ['nullable', 'string', 'url:https', 'max:2000'],
            'topics.*.min_read' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        try {
            $lesson = $this->service->commit($course, $request->user(), $validated);
        } catch (LessonImportException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'นำเข้าบทเรียนสำเร็จ',
            'newLesson' => new LessonResource($lesson),
            'topics_count' => $lesson->topics()->count(),
        ], 201);
    }
}
