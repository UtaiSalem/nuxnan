<?php

namespace App\Http\Controllers\Api\Learn\Course\groups;

use App\Http\Controllers\Controller;

use App\Models\Course;
use App\Models\CourseGroup;
use Illuminate\Http\Request;
use App\Http\Resources\Learn\info\CourseResource;
use App\Http\Resources\Learn\lessons\LessonResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Learn\groups\CourseGroupResource;
use App\Http\Resources\Learn\Course\groups\CourseGroupResourceV2;
use App\Http\Resources\Learn\Course\members\CourseMemberResourceV2;

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
            'groups'        => CourseGroupResource::collection($course->courseGroups),
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

    /**
     * V2: API endpoint for listing groups with pagination and filtering
     */
    public function indexV2(Course $course, Request $request)
    {
        $query = $course->courseGroups()->withCount('members');

        // Apply filters
        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        // Search by name or description
        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm);
            });
        }

        $groups = $query->orderBy('name')->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => CourseGroupResourceV2::collection($groups),
            'pagination' => [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ],
        ]);
    }

    /**
     * V2: API endpoint for storing a new group
     */
    public function storeV2(Course $course, Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'status' => 'nullable|boolean',
            ]);

            $newGroup = $course->courseGroups()->create([
                'user_id' => auth()->id(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? 1,
            ]);

            return response()->json([
                'success' => true,
                'data' => new CourseGroupResourceV2($newGroup),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * V2: API endpoint for updating a group
     */
    public function updateV2(Request $request, $groupId)
    {
        try {
            $group = CourseGroup::findOrFail($groupId);
            
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:500',
                'status' => 'nullable|boolean',
            ]);

            $group->update($validated);

            return response()->json([
                'success' => true,
                'data' => new CourseGroupResourceV2($group->fresh()),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * V2: API endpoint for getting group members
     */
    public function membersV2($groupId, Request $request)
    {
        try {
            $group = CourseGroup::findOrFail($groupId);
            
            $query = $group->members()->with('user');

            // Apply filters
            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            // Search by name or email
            if ($request->has('search') && $request->search) {
                $searchTerm = '%' . $request->search . '%';
                $query->whereHas('user', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm)
                      ->orWhere('email', 'like', $searchTerm);
                });
            }

            $members = $query->orderBy('order_number')->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => CourseMemberResourceV2::collection($members),
                'pagination' => [
                    'current_page' => $members->currentPage(),
                    'last_page' => $members->lastPage(),
                    'per_page' => $members->perPage(),
                    'total' => $members->total(),
                ],
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
