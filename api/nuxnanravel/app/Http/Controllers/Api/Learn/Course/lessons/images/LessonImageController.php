<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\images;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonImageController extends Controller
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
        try {
            // Check if user is course admin
            if (! $lesson->course->isAdmin(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์เพิ่มรูปภาพ',
                ], 403);
            }

            $uploadedImages = [];

            if ($request->hasFile('images')) {
                $images = $request->file('images');
                foreach ($images as $image) {
                    $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                    Storage::disk('public')->putFileAs('images/courses/lessons', $image, $fileName);
                    $newImage = $lesson->images()->create([
                        'filename' => $fileName,
                    ]);
                    $uploadedImages[] = $newImage;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'อัปโหลดรูปภาพสำเร็จ',
                'images' => $uploadedImages,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error uploading lesson images: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ',
            ], 500);
        }
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
            // Check if user is course admin (not just lesson creator)
            // This allows any course admin to manage lesson images
            if (! $lesson->course->isAdmin(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์ลบรูปภาพนี้',
                ], 403);
            }

            // Verify image belongs to this lesson
            if ($image->lesson_id !== $lesson->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'รูปภาพนี้ไม่ได้อยู่ในบทเรียนที่ระบุ',
                ], 404);
            }

            Storage::disk('public')->delete('images/courses/lessons/'.$image->filename);
            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบรูปภาพสำเร็จ',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error deleting lesson image: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบรูปภาพ',
            ], 500);
        }
    }
}
