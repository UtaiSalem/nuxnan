<?php

namespace App\Http\Controllers\Api\Learn\Course\questions;

use App\Http\Controllers\Controller;
use App\Models\Question;

class QuestionAnswerController extends Controller
{
    /**
     * Answer key of a single question.
     *
     * Restricted to the question's author and the course admins — the same
     * audience QuestionResource / QuestionOptionResource expose the key to.
     * Previously any authenticated user could read the correct option of any
     * question, including those of an exam still in progress.
     */
    public function index(Question $question)
    {
        $user = auth()->user();

        $isOwner = $user && (int) $question->user_id === (int) $user->id;
        $isCourseAdmin = $question->course && $question->course->isAdmin($user);

        abort_unless($isOwner || $isCourseAdmin, 403, 'ไม่มีสิทธิ์ดูเฉลยของคำถามนี้');

        return response()->json([
            'answer' => $question->correct_option_id,
        ], 200);
    }
}
