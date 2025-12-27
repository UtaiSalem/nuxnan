<?php

namespace App\Http\Controllers\Api\Learn\Course\questions;

use App\Http\Controllers\Controller;

use App\Models\Question;
use Illuminate\Http\Request;
use App\Models\QuestionOption;
use App\Models\UserAnswerQuestion;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\QuestionOptionResource;

class QuestionOptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Question $question, Request $request)
    {
        $option = $question->options()->create([
            'text' => $request->text,
        ]);

        if($request->hasFile('images')) {
            $images = $request->file('images');
            // $fileNames = [];
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/courses/quizzes/questions', $image, $fileName);
                // $fileNames[] = $fileName;
                $option->images()->create([
                    'filename' => $fileName
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'option' => new QuestionOptionResource($option),
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(QuestionOption $questionOption)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(QuestionOption $questionOption)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, QuestionOption $questionOption)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QuestionOption $option)
    {
        $question = $option->optionable;
        $userAnswers = UserAnswerQuestion::where('question_id', $question->id)
                                        ->where('answer_id', $option->id)
                                        ->get();
        // if ($userAnswers) {
            foreach ($userAnswers as $answer) {
                $answer->delete();
            }
        // }

        if($question->correct_option_id === $option->id){
            $question->update([
                'correct_option_id' => null,
                'correct_answers' => null,
            ]);
        };

        foreach ($option->images as $image) {
            Storage::disk('public')->delete($image->image_url);
        }
        $option->images()->delete();

        $option->delete();
    }
}
