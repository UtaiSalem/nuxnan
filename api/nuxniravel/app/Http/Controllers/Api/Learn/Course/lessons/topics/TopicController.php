<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\topics;

use App\Http\Controllers\Controller;

use App\Models\Topic;
use App\Models\Lesson;
use Illuminate\Http\Request;
use App\Http\Resources\Learn\Course\lessons\TopicResource;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Course\lessons\LessonResource;
use App\Http\Resources\Learn\Academy\AcademyResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Learn\Course\questions\QuestionResource;
use App\Http\Resources\Learn\Course\assignments\AssignmentResource;

class TopicController extends \App\Http\Controllers\Controller
{
    public function store(Lesson $lesson, Request $request)
    {
        $validatedData = $request->validate([
            'title'         => ['required', 'string'],
            'content'       => ['required', 'string'],
            'youtube_url'   => ['nullable', 'string'],
            'min_read'      => ['required', 'numeric'],
            'images.*'      => 'image|mimes:jpeg,png,jpg,gif,svg|max:4048|nullable',   
        ]);

        $topic = $lesson->topics()->create([
            'user_id'       => auth()->user()->id,
            'course_id'     => $lesson->course_id,
            'title'         => $validatedData['title'],
            'content'       => $validatedData['content'] === "null" ? null : $validatedData['content'],
            'youtube_url'   => $validatedData['youtube_url'] === "null" ? null : $validatedData['youtube_url'],
            'min_read'      => $validatedData['min_read'],
        ]);

        // a section to store images files
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/lessons/topics', $image, $fileName);
                
                $topic->images()->create([
                    'filename' => $fileName,
                ]);
            }
        }

        $lesson->increment('min_read', $topic->min_read);
        
        return response()->json([
            'success' => true,
            'newTopic' => new TopicResource(Topic::find($topic->id)),
        ], 200);
    }

    public function show(Topic $topic)
    {
        $lesson = $topic->lesson;
        $course = $lesson->course;
        $academy = $course->academy;

        if ($topic->user_id == auth()->id()) {
            $isCourseAdmin = true;
        }else{
            $isCourseAdmin = false;
        }

        return response()->json([
            'isCourseAdmin' => $isCourseAdmin,
            'academy'       => new AcademyResource($academy),
            'course'        => new CourseResource($lesson->course),
            'lesson'        => new LessonResource($lesson),
            'topic'         => new TopicResource($topic),
            'assignments'   => AssignmentResource::collection($topic->assignments),
            'questions'     => QuestionResource::collection($topic->questions),
            'imagePath'     => '/../../'
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

    public function update(Lesson $lesson, Topic $topic, Request $request,)
    {
        $lesson->decrement('min_read', $topic->min_read);
        // validate data
        $validatedData = $request->validate([
            'title'         => ['required', 'string'],
            'content'       => ['required', 'string'],
            'youtube_url'   => ['nullable', 'string'],
            'min_read'      => ['required', 'numeric'],
            'images.*'      => 'image|mimes:jpeg,png,jpg,gif,svg|max:4048|nullable',    
        ]);

        $topic->update([
            'title'         => $request->title,
            'content'       => $validatedData['content'] === "null" ? null : $validatedData['content'],
            'youtube_url'   => $validatedData['youtube_url'] === "null" ? null : $validatedData['youtube_url'],
            'min_read'      => $request->min_read
        ]);

        if($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/courses/lessons/topics', $image, $fileName);

                $topic->images()->create([
                    'filename' => $fileName
                ]);
            }
        }

        $lesson->increment('min_read', $topic->min_read);

        return response()->json([
            'success' => true,
            'topic' => new TopicResource($topic),
        ], 200);
    }

    public function destroy(Lesson $lesson, Topic $topic)
    {
        if ($topic->images->count() > 0) {
            foreach ($topic->images as $image) {
                Storage::disk('public')->delete('images/courses/lessons/topics/'. $image->filename);
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
            'success' => true
        ], 200);
    }

    public function assignmentsStore(Topic $topic, Request $request)
    {
        $assignment = $topic->assignments()->create([
            'title' => $request->title,
            'points' => $request->points,
        ]);

        if($request->hasFile('images')) {
            $images = $request->file('images');
            $fileNames = [];
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/topics/assignments', $image, $fileName);
                $fileNames[] = $fileName;

                $assignment->images()->create([
                    'image_url' => $image_url
                ]);
            }
        }
        return response()->json([
            'assignment' => new AssignmentResource($assignment),
        ], 200);
    }
}
