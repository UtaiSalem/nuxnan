<?php

namespace App\Http\Controllers\Api\Learn\Course\questions;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\questions\QuestionOptionResource;
use App\Models\Question;
use App\Models\QuestionImage;
use App\Models\QuestionOption;
use App\Models\UserAnswerQuestion;
use App\Services\CourseMediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'is_correct' => $request->is_correct ? true : false,
        ]);

        $this->storeOptionImages($option, $request);

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
    public function update(Request $request, ?Question $question, QuestionOption $option)
    {
        $request->validate([
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $option->update([
            'text' => $request->text ?? $option->text,
            'is_correct' => $request->has('is_correct') ? ($request->is_correct ? true : false) : $option->is_correct,
        ]);

        // Uploads used to be dropped on the floor here, so editing an option
        // never changed its picture. One picture per option: replace, not append.
        $this->storeOptionImages($option, $request, replace: true);

        return response()->json([
            'success' => true,
            'option' => new QuestionOptionResource($option->fresh('images')),
        ], 200);
    }

    /**
     * Persist uploaded option images.
     *
     * Option pictures live next to their question in the quiz question folder —
     * there is no `options/` subfolder for them.
     */
    private function storeOptionImages(QuestionOption $option, Request $request, bool $replace = false): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        if ($replace) {
            $media = app(CourseMediaService::class);

            foreach ($option->images as $old) {
                $media->deleteUnused('quiz_question_image', QuestionImage::class, 'filename', $old->filename, $old->id);
                $old->delete();
            }
        }

        foreach ($request->file('images') as $image) {
            $fileName = uniqid().'.'.$image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/courses/quizzes/questions', $image, $fileName);
            $option->images()->create([
                'filename' => $fileName,
            ]);
        }
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

        if ($question->correct_option_id === $option->id) {
            $question->update([
                'correct_option_id' => null,
                'correct_answers' => null,
            ]);
        }

        foreach ($option->images as $image) {
            Storage::disk('public')->delete($image->image_url);
        }
        $option->images()->delete();

        $option->delete();
    }
}
