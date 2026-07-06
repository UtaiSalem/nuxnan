<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\topics;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\questions\QuestionResource;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TopicQuestionController extends Controller
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
    public function store(Topic $topic, Request $request)
    {
        $question = $topic->questions()->create([
            'course_id' => $topic->course_id,
            'user_id' => auth()->id(),
            'text' => $request->text,
            'points' => $request->points,
        ]);

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $fileNames = [];
            foreach ($images as $image) {
                $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/courses/quizzes/questions', $image, $fileName);
                $fileNames[] = $fileName;

                $question->images()->create([
                    'filename' => $fileName,
                ]);
            }
        }

        return response()->json([
            'question' => new QuestionResource($question),
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Question $question)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Question $question)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Topic $topic, Question $question)
    {
        foreach ($question->images as $image) {
            Storage::disk('public')->delete($image->image_url);
        }
        $question->images()->delete();

        foreach ($question->options as $option) {
            foreach ($option->images as $image) {
                Storage::disk('public')->delete($image->image_url);
            }
            $option->images()->delete();
        }
        $question->options()->delete();

        $question->delete();
    }
}
