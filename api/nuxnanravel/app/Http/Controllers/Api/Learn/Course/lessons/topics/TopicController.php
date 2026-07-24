<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\topics;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Academy\AcademyResource;
use App\Http\Resources\Learn\Course\assignments\AssignmentResource;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Course\lessons\LessonResource;
use App\Http\Resources\Learn\Course\lessons\TopicResource;
use App\Http\Resources\Learn\Course\questions\QuestionResource;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\TopicImage;
use App\Services\CourseMediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TopicController extends Controller
{
    public function store(Lesson $lesson, Request $request)
    {
        if (! $lesson->course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'youtube_url' => ['nullable', 'string', 'url:https'],
            'min_read' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'],
        ]);

        $topic = DB::transaction(function () use ($lesson, $validatedData) {
            $topic = $lesson->topics()->create([
                'user_id' => auth()->id(),
                'academy_id' => $lesson->course->academy_id,
                'course_id' => $lesson->course_id,
                'title' => $validatedData['title'],
                'content' => $validatedData['content'] ?? null,
                'youtube_url' => $validatedData['youtube_url'] ?? null,
                'min_read' => $validatedData['min_read'] ?? 0,
            ]);

            $lesson->increment('min_read', $topic->min_read);

            return $topic;
        });

        // a section to store images files
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/lessons/topics', $image, $fileName);

                $topic->images()->create([
                    'filename' => $fileName,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'topic' => new TopicResource($topic->fresh()),
        ], 200);
    }

    public function show(Topic $topic)
    {
        $lesson = $topic->lesson;
        $course = $lesson->course;
        $academy = $course->academy;

        $isCourseAdmin = $course->isAdmin(auth()->user());

        return response()->json([
            'isCourseAdmin' => $isCourseAdmin,
            'academy' => new AcademyResource($academy),
            'course' => new CourseResource($lesson->course),
            'lesson' => new LessonResource($lesson),
            'topic' => new TopicResource($topic),
            'assignments' => AssignmentResource::collection($topic->assignments),
            'questions' => QuestionResource::collection($topic->questions),
            'imagePath' => '/../../',
        ]);
    }

    public function edit(Topic $topic)
    {
        // $lesson = $topic->lesson;
        // $course = $lesson->course;

        // return response()->json([
        //     'course' => $course,
        //     'lesson' => $lesson,
        //     'topic' => new TopicResourse($topic),
        //     'profilePath' => '../../'
        // ]);
    }

    public function update(Lesson $lesson, Topic $topic, Request $request)
    {
        if (! $lesson->course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'youtube_url' => ['nullable', 'string', 'url:https'],
            'min_read' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'],
        ]);

        DB::transaction(function () use ($lesson, $topic, $validatedData) {
            $oldMinRead = $topic->min_read;

            $topic->update([
                'title' => $validatedData['title'],
                'content' => $validatedData['content'] ?? null,
                'youtube_url' => $validatedData['youtube_url'] ?? null,
                'min_read' => $validatedData['min_read'] ?? 0,
            ]);

            $lesson->increment('min_read', ($topic->min_read - $oldMinRead));
        });

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/lessons/topics', $image, $fileName);

                $topic->images()->create([
                    'filename' => $fileName,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'topic' => new TopicResource($topic->fresh()),
        ], 200);
    }

    public function destroy(Lesson $lesson, Topic $topic, CourseMediaService $mediaService)
    {
        if (! $lesson->course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($topic->images->count() > 0) {
            foreach ($topic->images as $image) {
                $mediaService->deleteIfUnused(
                    'images/courses/lessons/topics/'.$image->filename,
                    TopicImage::class,
                    'filename',
                    $image->filename,
                    $image->id
                );
            }
            $topic->images()->delete();
        }

        // if ($topic->assignments) {
        //     foreach ($topic->assignments as $assignment) {
        //         if ($assignment->images) {
        //             foreach ($assignment->images as $image) {
        //                 Storage::disk('public')->delete($image->image_url);
        //                 $image->delete();
        //             }
        //         }
        //         if ($assignment->answers) {
        //             foreach ($assignment->answers as $answer) {
        //                 if ($answer->images) {
        //                     foreach ($answer->images as $image) {
        //                         Storage::disk('public')->delete($image->image_url);
        //                         $image->delete();
        //                     }
        //                 }
        //                 $answer->delete();
        //             }
        //         }

        //         $assignment->delete();
        //     }
        // }

        // if ($topic->questions) {
        //     foreach ($topic->questions as $question) {
        //         if ($question->images) {
        //             foreach ($question->images as $image) {
        //                 Storage::disk('public')->delete($image->image_url);
        //                 $image->delete();
        //             }
        //         }

        //         if($question->answers){
        //             foreach ($question->answers as $answer) {
        //                 if ($answer->images) {
        //                     foreach ($answer->images as $image) {
        //                         Storage::disk('public')->delete($image->image_url);
        //                         $image->delete();
        //                     }
        //                 }
        //                 $answer->delete();
        //             }
        //         }

        //         $question->delete();
        //     }
        // }

        $topic->delete();

        return response()->json([
            'success' => true,
        ], 200);
    }

    public function assignmentsStore(Topic $topic, Request $request)
    {
        $assignment = $topic->assignments()->create([
            'title' => $request->title,
            'points' => $request->points,
        ]);

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $fileNames = [];
            foreach ($images as $image) {
                $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/topics/assignments', $image, $fileName);
                $fileNames[] = $fileName;

                $assignment->images()->create([
                    'image_url' => $image_url,
                ]);
            }
        }

        return response()->json([
            'assignment' => new AssignmentResource($assignment),
        ], 200);
    }

    /**
     * Reorder topics in a lesson
     */
    public function reorder(Lesson $lesson, Request $request)
    {
        try {
            // Check permission
            if (! $lesson->course->isAdmin(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์จัดลำดับหัวข้อในบทเรียนนี้',
                ], 403);
            }

            // Validate request
            $validated = $request->validate([
                'topics' => 'required|array|min:1',
                'topics.*' => 'required|integer|exists:topics,id',
            ]);

            // Verify topic IDs belong to this lesson and all are present
            $lessonTopicIds = $lesson->topics()->pluck('id')->toArray();
            $incomingIds = $validated['topics'];

            if (count($incomingIds) !== count($lessonTopicIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'จำนวนหัวข้อไม่ถูกต้อง กรุณาส่งรายการหัวข้อทั้งหมดในบทเรียน',
                ], 422);
            }

            if (count($incomingIds) !== count(array_unique($incomingIds))) {
                return response()->json([
                    'success' => false,
                    'message' => 'มีไอดีหัวข้อซ้ำในรายการที่ส่งมา',
                ], 422);
            }

            foreach ($incomingIds as $id) {
                if (! in_array($id, $lessonTopicIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => "หัวข้อ ID {$id} ไม่ได้อยู่ในบทเรียนนี้",
                    ], 422);
                }
            }

            // Perform reorder in transaction
            DB::transaction(function () use ($incomingIds) {
                foreach ($incomingIds as $index => $id) {
                    Topic::where('id', $id)->update(['sort_order' => $index + 1]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'บันทึกลำดับหัวข้อสำเร็จ',
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error reordering topics: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการจัดลำดับหัวข้อ',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
