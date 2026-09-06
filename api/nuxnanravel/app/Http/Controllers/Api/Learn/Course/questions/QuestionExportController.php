<?php

namespace App\Http\Controllers\Api\Learn\Course\questions;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseQuiz;
use App\Models\Lesson;
use App\Services\Export\QuestionExportService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class QuestionExportController extends Controller
{
    public function __construct(private QuestionExportService $service) {}

    public function lessonExport(Lesson $lesson)
    {
        Gate::authorize('manage', $lesson->course);

        return $this->download($lesson, $lesson->title);
    }

    public function quizExport(Course $course, CourseQuiz $quiz)
    {
        Gate::authorize('manage', $course);
        abort_if($quiz->course_id !== $course->id, 404);

        return $this->download($quiz, $quiz->title);
    }

    private function download(Model $questionable, ?string $title)
    {
        if ($questionable->questions()->count() === 0) {
            return response()->json(['success' => false, 'message' => 'ยังไม่มีข้อสอบให้ดาวน์โหลด'], 422);
        }

        $path = $this->service->build($questionable, $title ?: 'ข้อสอบ');

        $sanitized = trim(preg_replace('/[\\\\\/:\*\?"<>|[:cntrl:]]/u', '', (string) $title));
        $sanitized = mb_substr($sanitized, 0, 60);

        if ($sanitized === '') {
            $sanitized = 'ข้อสอบ';
        }

        $filename = $sanitized.'-ข้อสอบ-'.now()->format('Ymd').'.xlsx';

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
