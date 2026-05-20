<?php

namespace App\Http\Controllers\Api\Learn\Course\groups;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Models\Course;
use App\Models\CourseGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CourseGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Course $course)
    {
        // Get ungrouped members (group_id is NULL, exclude admins role=4)
        $ungroupedMembers = $course->courseMembers()
            ->whereNull('group_id')
            ->where('role', '!=', 4)
            ->with('user')
            ->orderBy('order_number')
            ->get();

        // Eager load members.user to avoid N+1 queries
        $courseGroups = $course->courseGroups()->with(['members.user'])->get();

        return response()->json([
            'success' => true,
            'isCourseAdmin' => $course->isAdmin(auth()->user()),
            'course' => new CourseResource($course),
            'groups' => CourseGroupResource::collection($courseGroups),
            'ungrouped_members' => $ungroupedMembers->map(function ($member) {
                $user = $member->user;
                $avatarUrl = $this->getAvatarUrl($user);

                return [
                    'id' => $member->id,
                    'course_id' => $member->course_id,
                    'group_id' => null,
                    'user_id' => $member->user_id,
                    'member_name' => $member->member_name,
                    'member_code' => $member->member_code,
                    'order_number' => $member->order_number,
                    'achieved_score' => $member->achieved_score ?? 0,
                    'bonus_points' => $member->bonus_points ?? 0,
                    'role' => $member->role,
                    'status' => $member->status,
                    'course_member_status' => $member->course_member_status,
                    'enrollment_date' => $member->enrollment_date,
                    'last_activity_at' => $member->last_accessed_at,
                    'lessons_completed' => $member->lessons_completed ?? 0,
                    'attendance_rate' => $member->attendance_rate ?? 0,
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar' => $avatarUrl,
                        'email' => $user->email,
                    ] : null,
                    'avatar' => $avatarUrl,
                    'name' => $member->member_name ?? $user?->name ?? 'Unknown User',
                    'group' => null,
                ];
            }),
            'courseMemberOfAuth' => $course->courseMembers()->where('user_id', auth()->id())->first(),
        ]);
    }

    /**
     * Generate avatar URL for a user (matches CourseGroupResource logic)
     */
    private function getAvatarUrl($user): string
    {
        if (! $user) {
            return 'https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF';
        }

        if ($user->profile_photo_path) {
            if (filter_var($user->profile_photo_path, FILTER_VALIDATE_URL)) {
                return $user->profile_photo_path;
            }

            return url(Storage::url($user->profile_photo_path));
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($user->name ?? 'User').'&color=7F9CF5&background=EBF4FF';
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
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
                'privacy' => 'nullable|in:public,private',
                'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'max_members' => 'nullable|integer|min:1',
                'auto_accept_member' => 'nullable|boolean',
                // 'cover'             => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:4096',
            ]);

            // $newGroups = $validated;
            if ($request->file('cover')) {
                $cover_file = $request->file('cover');
                $cover_filename = uniqid().'.'.$cover_file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/groups', $cover_file, $cover_filename);
                // $validated['image_url'] = $cover_filename;
            }

            $groupData = [
                'user_id' => auth()->id(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'image_url' => $cover_filename ?? null,
            ];

            if (Schema::hasColumn('course_groups', 'privacy')) {
                $groupData['privacy'] = $validated['privacy'] ?? 'public';
            }

            if (Schema::hasColumn('course_groups', 'color')) {
                $groupData['color'] = $validated['color'] ?? '#3B82F6';
            }

            if (Schema::hasColumn('course_groups', 'max_members')) {
                $groupData['max_members'] = $validated['max_members'] ?? null;
            }

            if (Schema::hasColumn('course_groups', 'auto_accept_member')) {
                $groupData['auto_accept_member'] = $validated['auto_accept_member'] ?? 1;
            }

            $newGroup = $course->courseGroups()->create($groupData);

            // Update groups count in course
            $course->increment('groups');
            $newGroup->load('members.user');

            return response()->json([
                'success' => true,
                'group' => new CourseGroupResource($newGroup),
                'newGroup' => new CourseGroupResource($newGroup),
                'groupsCount' => $course->groups,
            ], 200);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course, CourseGroup $group)
    {
        return response()->json([
            'success' => true,
            'group' => new CourseGroupResource($group),
            'isMember' => $group->course_group_members()->where('user_id', auth()->id())->exists(),
            'isAdmin' => $group->course_group_members()->where('user_id', auth()->id())->where('role', 'admin')->exists() || $course->isAdmin(auth()->user()),
        ]);
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
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
                'privacy' => 'nullable|in:public,private',
                'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'max_members' => 'nullable|integer|min:1',
                'auto_accept_member' => 'nullable|boolean',
            ]);

            $data = [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ];

            foreach (['privacy', 'color', 'max_members', 'auto_accept_member'] as $column) {
                if (Schema::hasColumn('course_groups', $column) && array_key_exists($column, $validated)) {
                    $data[$column] = $validated[$column];
                }
            }

            if ($request->file('cover')) {
                // Delete old cover
                if ($group->image_url) {
                    Storage::disk('public')->delete('images/courses/groups/'.$group->image_url);
                }

                $cover_file = $request->file('cover');
                $cover_filename = uniqid().'.'.$cover_file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/groups', $cover_file, $cover_filename);
                $data['image_url'] = $cover_filename;
            }

            $group->update($data);
            $group->load('members.user');

            return response()->json([
                'success' => true,
                'group' => new CourseGroupResource($group),
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
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($group->image_url) {
            Storage::disk('public')->delete('images/courses/groups/'.$group->image_url);
        }

        $group->delete();

        // Update groups count in course
        $course->decrement('groups');

        return response()->json([
            'success' => true,
            'groupsCount' => $course->groups,
        ], 200);
    }
}
