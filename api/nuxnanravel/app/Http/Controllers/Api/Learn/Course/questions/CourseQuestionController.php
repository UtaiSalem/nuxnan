<?php

namespace App\Http\Controllers\Api\Learn\Course\questions;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\Models\Course;
use App\Models\Question;
use App\Services\CourseScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseQuestionController extends Controller
{
    public function store(Course $course, Request $request)
    {
        $question = $course->questions()->create([
            'user_id' => auth()->id(),
            'text' => $request->text,
            'points' => $request->points,
        ]);

        app(CourseScoreService::class)->syncCourseTotalScore($course);

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $fileNames = [];
            foreach ($images as $image) {
                $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/courses/questions', $image, $fileName);
                $fileNames[] = $fileName;

                $question->images()->create([
                    'image_url' => $image_url,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'question' => new QuestionResource($question),
        ], 200);
    }

    public function destroy(Course $course, Question $question)
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
        app(CourseScoreService::class)->syncCourseTotalScore($course);
        $question->delete();
    }
}
