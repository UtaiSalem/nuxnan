<?php

namespace App\Http\Controllers\Api\Learn\Course\quizzes;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseQuiz;
use Illuminate\Http\Request;
use App\Models\CourseQuizResult;
use App\Http\Requests\StoreCourseQuizResultRequest;
use App\Http\Requests\UpdateCourseQuizResultRequest;
use Illuminate\Support\Facades\DB;

class CourseQuizResultController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Course $course, CourseQuiz $quiz, Request $request)
    {

        $quizResult = $course->courseQuizResults()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($quizResult) {
            $quizResult->update([
                'status'        => 0,
                'started_at'    => date('Y-m-d H:i:s'),
                'completed_at'  => $request->completed_at,
            ]);

            return response()->json([
                'status'        => true,
                'quizResult'    => $quizResult
            ], 201);

        }else {
            $quizResult = CourseQuizResult::create([
                'user_id'       => auth()->id(),
                'course_id'     => $course->id,
                'quiz_id'       => $quiz->id,
                'status'        => 0,
                'started_at'    => date('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'status'        => true,
                'quizResult'    => $quizResult
            ], 201);
        }
    
    }


}
