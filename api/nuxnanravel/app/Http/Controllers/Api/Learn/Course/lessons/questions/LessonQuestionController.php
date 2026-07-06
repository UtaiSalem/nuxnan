<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\questions;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\questions\QuestionResource;
use App\Models\Lesson;
use App\Models\Question;
use App\Services\CourseScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Lesson $lesson)
    {
        $questions = $lesson->questions()->with(['options', 'images'])->get();

        return QuestionResource::collection($questions);
    }

    public function store(Lesson $lesson, Request $request)
    {
        $question = $lesson->questions()->create([
            'course_id' => $lesson->course_id,
            'user_id' => auth()->id(),
            'text' => $request->text,
            'points' => $request->points,
            'type' => $request->type ?? 'multiple_choice',
            'explanation' => $request->explanation,
        ]);

        if ($lesson->course) {
            app(CourseScoreService::class)->syncCourseTotalScore($lesson->course);
        }

        if ($request->filled('options')) {
            foreach ($request->options as $index => $optionData) {
                if (is_array($optionData)) {
                    $newOption = $question->options()->create([
                        'text' => $optionData['text'] ?? '',
                        'is_correct' => filter_var($optionData['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'explanation' => $optionData['explanation'] ?? null,
                    ]);

                    if ($request->hasFile("options.$index.image")) {
                        $image = $request->file("options.$index.image");
                        $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                        // Correct path: lessons/questions/options
                        $image_path = Storage::disk('public')->putFileAs('images/courses/lessons/questions/options', $image, $fileName);
                        $newOption->images()->create([
                            'filename' => $fileName,
                        ]);
                    }
                }
            }
        }

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                // Correct path: lessons/questions
                $image_path = Storage::disk('public')->putFileAs('images/courses/lessons/questions', $image, $fileName);
                $question->images()->create([
                    'filename' => $fileName,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'question' => new QuestionResource($question->load('options', 'images')),
        ]);
    }

    public function show(Lesson $lesson, Question $question)
    {
        return new QuestionResource($question->load('options', 'images'));
    }

    public function update(Request $request, Lesson $lesson, Question $question)
    {
        $question->update([
            'text' => $request->text,
            'points' => $request->points,
            'explanation' => $request->explanation,
        ]);

        // Sync Options
        if ($request->filled('options')) {
            $processedIds = [];

            foreach ($request->options as $index => $optionData) {
                if (isset($optionData['id']) && $optionData['id']) {
                    // Update
                    $option = $question->options()->find($optionData['id']);
                    if ($option) {
                        $option->update([
                            'text' => $optionData['text'],
                            'is_correct' => filter_var($optionData['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'explanation' => $optionData['explanation'] ?? null,
                        ]);
                        $processedIds[] = $option->id;

                        // Check for new image
                        if ($request->hasFile("options.$index.image")) {
                            // Delete old image if needed (not implemented here)

                            $image = $request->file("options.$index.image");
                            $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                            $image_path = Storage::disk('public')->putFileAs('images/courses/lessons/questions/options', $image, $fileName);
                            $option->images()->create([
                                'filename' => $fileName,
                            ]);
                        }
                    }
                } else {
                    // Create
                    $newOption = $question->options()->create([
                        'text' => $optionData['text'] ?? '',
                        'is_correct' => filter_var($optionData['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'explanation' => $optionData['explanation'] ?? null,
                    ]);
                    $processedIds[] = $newOption->id;

                    if ($request->hasFile("options.$index.image")) {
                        $image = $request->file("options.$index.image");
                        $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                        $image_path = Storage::disk('public')->putFileAs('images/courses/lessons/questions/options', $image, $fileName);
                        $newOption->images()->create([
                            'filename' => $fileName,
                        ]);
                    }
                }
            }

            // Delete removed options
            $question->options()->whereNotIn('id', $processedIds)->delete();
        }

        // Handle new images
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                $image_path = Storage::disk('public')->putFileAs('images/courses/lessons/questions', $image, $fileName);
                $question->images()->create([
                    'filename' => $fileName,
                ]);
            }
        }

        // Handle deleted images
        if ($request->filled('deleted_images')) {
            foreach ($request->deleted_images as $imgId) {
                $img = $question->images()->find($imgId);
                if ($img) {
                    $path = 'images/courses/lessons/questions/'.$img->filename;
                    \Log::info('Attempting to delete image: '.$img->id.' path: '.$path);

                    if (Storage::disk('public')->exists($path)) {
                        \Log::info('File exists, deleting...');
                        Storage::disk('public')->delete($path);
                    } else {
                        \Log::warning('File not found at path: '.$path);
                    }
                    $img->delete();
                }
            }
        }

        return response()->json([
            'success' => true,
            'question' => new QuestionResource($question->load('options', 'images')),
        ]);
    }

    public function destroy(Lesson $lesson, Question $question)
    {
        try {
            // Eager load relationships to ensure they exist before we iterate
            $question->load(['images', 'options.images']);

            // 1. Delete Question Images
            if ($question->images) {
                foreach ($question->images as $image) {
                    if (! empty($image->filename)) {
                        $path = 'images/courses/lessons/questions/'.$image->filename;
                        if (Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->delete($path);
                        }
                    }
                    $image->delete();
                }
            }

            // 2. Delete Options and Option Images
            if ($question->options) {
                foreach ($question->options as $option) {
                    if ($option->images) {
                        foreach ($option->images as $image) {
                            if (! empty($image->filename)) {
                                $path = 'images/courses/lessons/questions/options/'.$image->filename;
                                if (Storage::disk('public')->exists($path)) {
                                    Storage::disk('public')->delete($path);
                                }
                            }
                            $image->delete(); // Delete the image record
                        }
                    }
                    $option->delete(); // Delete the option record
                }
            }

            // 3. Update Course Score (Safely)
            if ($lesson->course) {
                app(CourseScoreService::class)->syncCourseTotalScore($lesson->course);
            }

            // 4. Delete the Question itself
            $question->delete();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            \Log::error('Error deleting question ID '.$question->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete question. Please check server logs.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
