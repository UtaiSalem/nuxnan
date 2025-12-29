<?php

namespace App\Http\Controllers\Api\Learn\Course\groups;

use App\Http\Controllers\Controller;

use App\Models\Course;
use App\Models\CourseGroup;
use Illuminate\Http\Request;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Course\lessons\LessonResource;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;
use App\Http\Resources\Learn\Course\members\CourseMemberResource;
use Illuminate\Support\Facades\Storage;

class CourseGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Course $course)
    {
        return response()->json([
            'isCourseAdmin' => $course->user_id === auth()->id(),
            'course'        => new CourseResource($course),
            // 'groups'        => CourseGroupResource::collection($course->courseGroups),
            'courseMemberOfAuth'=> $course->courseMembers()->where('user_id', auth()->id())->first(),
        ]);
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
    public function store(Course $course, Request $request)
    {
        try {
            $request->validate([
                'name'              => 'required|string|max:255',
                'description'       => 'nullable|string|max:255',
                // 'cover'             => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:4096',
            ]);

            // $newGroups = $validated;
            if($request->file('cover')) {
                $cover_file = $request->file('cover');
                $cover_filename =  uniqid().'.'.$cover_file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/groups', $cover_file, $cover_filename);
                // $validated['image_url'] = $cover_filename;
            }

            $newGroup = $course->courseGroups()->create([
                'user_id'           => auth()->id(),
                'name'              => $request->name,
                'description'       => $request->get('description', null),
                'image_url'         => $cover_filename ?? null,
            ]);

            return response()->json([
                'success' => true,
                'newGroup' => new CourseGroupResource(CourseGroup::find($newGroup->id)),
            ], 200);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CourseGroup $courseGroup)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CourseGroup $courseGroup)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Course $course, CourseGroup $group, Request $request)
    {
        try {
            $currentGroup = $group->update($request->all());

            return response()->json([
                'success' => true,
                'group' => $currentGroup,
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course, CourseGroup $group)
    {
        if ($group->image_url) {
            Storage::disk('public')->delete('images/courses/groups/'. $group->image_url); 
        }

        $group->delete();

        return response()->json([
            'success' => true,
        ], 200);
    }
}
