<?php

namespace App\Http\Controllers\Api\Learn\Course\questions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learn\Course\ImportQuestionsRequest;
use App\Models\Course;
use App\Models\CourseQuiz;
use App\Models\Lesson;
use App\Services\Import\QuestionImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuestionImportController extends Controller
{
    private QuestionImportService $service;

    public function __construct(QuestionImportService $service)
    {
        $this->service = $service;
    }

    public function lessonTemplate(Lesson $lesson)
    {
        Gate::authorize('manage', $lesson->course);

        return $this->templateResponse();
    }

    public function lessonPreview(ImportQuestionsRequest $request, Lesson $lesson)
    {
        Gate::authorize('manage', $lesson->course);

        return $this->previewResponse($request);
    }

    public function lessonImport(Request $request, Lesson $lesson)
    {
        Gate::authorize('manage', $lesson->course);

        return $this->importResponse($request, $lesson, $lesson->course);
    }

    public function quizTemplate(Course $course, CourseQuiz $quiz)
    {
        Gate::authorize('manage', $course);
        abort_if($quiz->course_id !== $course->id, 404);

        return $this->templateResponse();
    }

    public function quizPreview(ImportQuestionsRequest $request, Course $course, CourseQuiz $quiz)
    {
        Gate::authorize('manage', $course);
        abort_if($quiz->course_id !== $course->id, 404);

        return $this->previewResponse($request);
    }

    public function quizImport(Request $request, Course $course, CourseQuiz $quiz)
    {
        Gate::authorize('manage', $course);
        abort_if($quiz->course_id !== $course->id, 404);

        return $this->importResponse($request, $quiz, $course);
    }

    private function templateResponse()
    {
        $path = $this->service->buildTemplate();

        return response()->download($path, 'question-import-template.xlsx')->deleteFileAfterSend(true);
    }

    private function previewResponse(ImportQuestionsRequest $request)
    {
        try {
            $parsed = $this->service->parse($request->file('file'));
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if (count($parsed['rows']) > 200) {
            return response()->json(['success' => false, 'message' => 'อัปโหลดได้สูงสุด 200 ข้อต่อครั้ง'], 422);
        }

        $validated = $this->service->validateRows($parsed['rows']);

        $validCount = 0;
        $invalidCount = 0;
        $warningsCount = 0;

        foreach ($validated as $row) {
            if (count($row['errors']) > 0) {
                $invalidCount++;
            } else {
                $validCount++;
            }
            if (count($row['warnings']) > 0) {
                $warningsCount++;
            }
        }

        return response()->json([
            'success' => true,
            'summary' => [
                'total' => count($validated),
                'valid' => $validCount,
                'invalid' => $invalidCount,
                'warnings' => $warningsCount,
            ],
            'rows' => $validated,
        ]);
    }

    private function importResponse(Request $request, $questionable, Course $course)
    {
        $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:200'],
            'rows.*.text' => ['required', 'string', 'max:5000'],
            'rows.*.options' => ['required', 'array', 'min:2', 'max:6'],
            'rows.*.options.*' => ['required', 'string', 'max:1000'],
            'rows.*.correct' => ['required', 'integer', 'min:0', 'max:5'],
            'rows.*.points' => ['nullable', 'integer', 'min:0', 'max:100'],
            'rows.*.pp_fine' => ['nullable', 'integer', 'min:0'],
            'rows.*.explanation' => ['nullable', 'string', 'max:5000'],
        ]);

        $payloadRows = [];
        $hasErrors = false;

        foreach ($request->input('rows') as $idx => $r) {
            if ($r['correct'] >= count($r['options'])) {
                $hasErrors = true;
            }
            $payloadRows[] = [
                'row_number' => $idx + 2,
                'text' => $r['text'],
                'options' => $r['options'],
                'correct_raw' => (string) ($r['correct'] + 1), // hack for validateRows to work properly
                'points_raw' => (string) ($r['points'] ?? '1'),
                'explanation' => $r['explanation'] ?? '',
                'pp_fine_raw' => (string) ($r['pp_fine'] ?? '0'),
            ];
        }

        $validated = $this->service->validateRows($payloadRows);
        foreach ($validated as $v) {
            if (count($v['errors']) > 0) {
                $hasErrors = true;
                break;
            }
        }

        if ($hasErrors) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลบางแถวไม่ถูกต้อง',
                'rows' => $validated,
            ], 422);
        }

        $validRows = array_column($validated, 'data');
        $result = $this->service->commit($questionable, $course, $validRows, $request->user());

        return response()->json([
            'success' => true,
            'imported' => $result['imported'],
        ]);
    }
}
