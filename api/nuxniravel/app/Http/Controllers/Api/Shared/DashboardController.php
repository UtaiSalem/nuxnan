<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;


use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $auth = auth()->user();

        $authQuestionsAnswer = $auth->answerQuestions;
        $authAssignmentsAnswer = $auth->answerAssignments;

        return response()->json([
            'profilePath' => '/../',
            'question_answers' => $authQuestionsAnswer,
            'assignment_answers' => $authAssignmentsAnswer,
        ]);
    }
}
