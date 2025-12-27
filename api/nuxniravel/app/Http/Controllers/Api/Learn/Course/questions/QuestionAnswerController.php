<?php

namespace App\Http\Controllers\Api\Learn\Course\questions;

use App\Http\Controllers\Controller;
use App\Models\Question;

class QuestionAnswerController extends Controller
{
    public function index(Question $question)
    {
        return response()->json([
            'answer' => $question->correct_option_id
        ], 200);
    }
}
