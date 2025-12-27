<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\questions;

use App\Models\Lesson;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\QuestionResource;

class LessonQuestionController extends \App\Http\Controllers\Controller
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
    public function store(Lesson $lesson, Request $request)
    {
        $question = $lesson->questions()->create([
            'user_id' => auth()->id(),
            'text' => $request->text,
            'points' => $request->points,
        ]);

        if($request->hasFile('images')) {
            $images = $request->file('images');
            $fileNames = [];
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/lessons/questions', $image, $fileName);
                $fileNames[] = $fileName;

                $question->images()->create([
                    'image_url' => $image_url
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lesson $lesson, Question $question)
    {
        if ($question->images) {
            foreach ($question->images as $image) {
                Storage::disk('public')->delete($image->image_url);
            }
            $question->images()->delete();
        }

        if ($question->options) {
            foreach ($question->options as $option) {
                if ($option->images) {
                    foreach ($option->images as $image) {
                        Storage::disk('public')->delete($image->image_url);
                    }
                    $option->images()->delete();
                }
            }
            $question->options()->delete();
        }
        
        $question->delete();
    }
}
