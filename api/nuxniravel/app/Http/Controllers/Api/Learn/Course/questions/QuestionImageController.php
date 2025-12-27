<?php

namespace App\Http\Controllers\Api\Learn\Course\questions;

use App\Http\Controllers\Controller;

use App\Models\Question;
use Illuminate\Http\Request;
use App\Models\QuestionImage;
use Illuminate\Support\Facades\Storage;

class QuestionImageController extends Controller
{
    public function store(Request $request, Question $question)
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        foreach ($request->file('images') as $image) {
            $imageName = uniqid() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('images/courses/questions', $imageName, 'public');

            $question->images()->create([
                'image_url' => $imageName,
            ]);
        }

        return response()->json([
            'success' => true,
        ], 200);
    }

    public function destroy(Question $question, QuestionImage $image)
    {
        // $image = $question->images()->findOrFail($imageId);
        
        Storage::disk('public')->delete('images/courses/questions/'.$image->image_url);
        
        $image->delete();

        return response()->json([
            'success' => true,
        ], 200);
    }

    public function destroyAll(Question $question)
    {
        foreach ($question->images as $image) {
            Storage::disk('public')->delete('images/courses/questions'.$image->image_url);
        }

        $question->images()->delete();

        return response()->json([
            'success' => true,
        ], 200);
    }
}
