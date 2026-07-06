<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\topics;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTopicImageRequest;
use App\Http\Requests\UpdateTopicImageRequest;
use App\Models\Topic;
use App\Models\TopicImage;
use App\Services\CourseMediaService;
use Illuminate\Support\Facades\Storage;

class TopicImageController extends Controller
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
    public function store(StoreTopicImageRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(TopicImage $topicImage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TopicImage $topicImage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTopicImageRequest $request, TopicImage $topicImage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Topic $topic, TopicImage $image, CourseMediaService $mediaService)
    {
        // 1. Ownership check: image ต้องเป็นของ topic นี้
        if ($image->topic_id !== $topic->id) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found for this topic',
            ], 404);
        }

        // 2. Authorization: ต้องเป็น course admin
        if (! $topic->course->isAdmin(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // 3. Safe delete: ใช้ deleteIfUnused กันลบไฟล์ที่ share จาก duplicate course
        $mediaService->deleteIfUnused(
            'images/courses/lessons/topics/'.$image->filename,
            TopicImage::class,
            'filename',
            $image->filename,
            $image->id
        );

        // 4. ลบ DB record
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted',
        ], 200);
    }
}
