<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\images;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\images\LessonImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonImageController extends \App\Http\Controllers\Controller
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
    public function store(Request $request)
    {
        if($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/lessons', $image, $fileName);
                $lesson->images()->create([
                    'filename' => $fileName,
                ]);
            }
        }

        return response()->json([
            
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(LessonImage $lessonImage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LessonImage $lessonImage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LessonImage $lessonImage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lesson $lesson, LessonImage $image)
    {
        try {
            if ($lesson->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            Storage::disk('public')->delete('images/courses/lessons/'. $image->filename);
            $image->delete();
            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson not found'
            ], 404);
        }

    }
}
