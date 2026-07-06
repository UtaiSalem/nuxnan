<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Academy\AcademyResource;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class AcademyController extends Controller
{
    // Middleware is now handled in route definitions (routes/learn/academy.php)

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(['success' => true]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['success' => true]);
    }

    public function create_course(Academy $academy)
    {
        $isAcademyAdmin = $academy->user_id == auth()->id();

        return response()->json([
            'academy' => new AcademyResource($academy),
            'courses' => CourseResource::collection($academy->courses()->paginate()),
            'isAcademyAdmin' => $isAcademyAdmin,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        if (auth()->user()->pp < 1000000) {
            return response()->json([
                'success' => false,
                'message' => 'แต้มสะสมไม่เพียงพอ, กรุณาเพิ่มแต้มสะสม',
            ], 200);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'slogan' => 'required|string',
            'address' => 'required|string',
            'autoAcceptMember' => 'required|string',
            'membershipFees' => 'required|integer',
            'logo' => 'image|mimes:jpg,jpeg,png,gif,svg',
            'cover' => 'image|mimes:jpg,jpeg,png,gif,svg',
        ]);

        // return $validated['autoAcceptMember'] === true ? true: false;

        try {
            $authUser = auth()->user();

            if ($request->hasFile('logo')) {
                // $logo = $request->file('logo');
                $logo = $validated['logo'];
                $logo_name = uniqid().'.'.$logo->getClientOriginalExtension();

                // $logo_image = Image::make($logo->getRealPath());
                // $logo_image->resize(300, 300, function ($constraint) {
                //     $constraint->aspectRatio();
                // });

                $logo_url = Storage::disk('public')->putFileAs('images/academies/logos', $logo, $logo_name);
            }

            if ($request->hasFile('cover')) {
                $cover = $validated['cover'];
                $cover_name = uniqid().'.'.$cover->getClientOriginalExtension();

                $cover_url = Storage::disk('public')->putFileAs('images/academies/covers', $cover, $cover_name);
            }

            $academy = new Academy;
            $academy->user_id = auth()->id();
            $academy->name = $validated['name'];
            $academy->slogan = $validated['slogan'];
            $academy->address = $validated['address'];
            $academy->membership_fees_points = $validated['membershipFees'];
            $academy->director = auth()->id();
            $academy->logo = $logo_name ?? null;
            $academy->cover = $cover_name ?? null;

            $academy->save();

            $academy->academySetting()->create([
                'auto_accept_members' => $validated['autoAcceptMember'] === 'true' ? true : false,
            ]);

            auth()->user()->decrement('pp', 860000);

            return response()->json([
                'success' => true,
                'academy' => $academy->id,
            ], 200);

        } catch (\Throwable $th) {
            return throw $th;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Academy $academy)
    {
        $isAcademyAdmin = $academy->user_id == auth()->id();

        // Get user's membership and role in this academy
        $membership = null;
        $myRole = null;

        if (auth()->check()) {
            $membership = AcademyMember::where('academy_id', $academy->id)
                ->where('user_id', auth()->id())
                ->with('academyRole')
                ->first();

            if ($membership && $membership->academyRole) {
                $myRole = [
                    'id' => $membership->academyRole->id,
                    'name' => $membership->academyRole->name,
                    'display_name' => $membership->academyRole->display_name_th,
                    'display_name_en' => $membership->academyRole->display_name_en,
                    'permissions' => $membership->academyRole->permissions,
                    'color' => $membership->academyRole->color,
                    'icon' => $membership->academyRole->icon,
                    'is_system' => $membership->academyRole->is_system,
                ];
            } elseif ($isAcademyAdmin) {
                // Academy owner automatically has owner role
                $myRole = [
                    'name' => 'owner',
                    'display_name' => 'เจ้าของสถาบัน',
                    'display_name_en' => 'Owner',
                    'permissions' => ['*'], // Full access
                    'color' => 'bg-purple-100 text-purple-700',
                    'icon' => 'fluent:crown-24-filled',
                    'is_system' => true,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'academy' => new AcademyResource($academy),
            'isAcademyAdmin' => $isAcademyAdmin,
            'membership' => $membership ? [
                'id' => $membership->id,
                'status' => $membership->status,
                'role' => $membership->role,
                'joined_at' => $membership->created_at,
            ] : null,
            'myRole' => $myRole,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Academy $academy)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'cover' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('cover')) {
            if ($academy->cover && ($academy->cover !== 'default_cover.png')) {
                Storage::disk('public')->delete($academy->cover);
            }

            $cover = $validated['cover'];

            $cover_name = uniqid().'.'.$cover->getClientOriginalExtension();
            $cover_path = Storage::disk('public')->putFileAs('images/academies/covers', $cover, $cover_name);
            $academy->cover = $cover_name;
        }

        if ($request->hasFile('logo')) {
            if ($academy->logo && ($academy->logo !== 'default_logo.png')) {
                Storage::disk('public')->delete($academy->logo);
            }

            $logo = $validated['logo'];

            $logo_name = uniqid().'.'.$logo->getClientOriginalExtension();
            $logo_path = Storage::disk('public')->putFileAs('images/academies/logos', $logo, $logo_name);
            $academy->logo = $logo_name;
        }

        if ($request->name) {
            $academy->name = $request->name;
        }
        if ($request->slogan) {
            $academy->slogan = $request->slogan;
        }
        if ($request->address) {
            $academy->address = $request->address;
        }

        $academy->update();

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Academy $academy)
    {
        //
    }

    public function joinAcademy(Academy $academy)
    {
        $academy->members()->attach(auth()->id());

        return redirect()->back();
    }

    public function leaveAcademy(Academy $academy)
    {
        $academy->members()->detach(auth()->id());

        return redirect()->back();
    }

    public function acceptMember(Academy $academy, $memberId)
    {
        $academy->members()->updateExistingPivot($memberId, ['status' => 'accepted']);

        return redirect()->back();
    }

    public function rejectMember(Academy $academy, $memberId)
    {
        $academy->members()->updateExistingPivot($memberId, ['status' => 'rejected']);

        return redirect()->back();
    }

    public function removeMember(Academy $academy, $memberId)
    {
        $academy->members()->detach($memberId);

        return redirect()->back();
    }

    public function updateAcademySetting(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'autoAcceptMember' => 'required|string',
        ]);

        $academy->academySetting->update([
            'auto_accept_members' => $validated['autoAcceptMember'] === 'true' ? true : false,
        ]);

        return redirect()->back();
    }

    public function updateMembershipFees(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'membershipFees' => 'required|integer',
        ]);

        $academy->update([
            'membership_fees_points' => $validated['membershipFees'],
        ]);

        return redirect()->back();
    }

    public function updateAcademyLogo(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png,gif,svg',
        ]);

        if ($academy->logo && ($academy->logo !== 'default_logo.png')) {
            Storage::disk('public')->delete($academy->logo);
        }

        $logo = $validated['logo'];

        $logo_name = uniqid().'.'.$logo->getClientOriginalExtension();
        $logo_path = Storage::disk('public')->putFileAs('images/academies/logos', $logo, $logo_name);
        $academy->logo = $logo_name;

        $academy->update();

        return redirect()->back();
    }

    public function updateAcademyCover(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'cover' => 'required|image|mimes:jpg,jpeg,png,gif,svg',
        ]);

        if ($academy->cover && ($academy->cover !== 'default_cover.png')) {
            Storage::disk('public')->delete($academy->cover);
        }

        $cover = $validated['cover'];

        $cover_name = uniqid().'.'.$cover->getClientOriginalExtension();
        $cover_path = Storage::disk('public')->putFileAs('images/academies/covers', $cover, $cover_name);
        $academy->cover = $cover_name;

        $academy->update();

        return redirect()->back();
    }

    public function searchAcademies(Request $request)
    {
        $academies = Academy::where('name', 'like', '%'.$request->search.'%')->get();

        return response()->json([
            'academies' => AcademyResource::collection($academies),
        ], 200);
    }

    public function searchAcademiesMembers(Academy $academy, Request $request)
    {
        $members = $academy->members()->where('name', 'like', '%'.$request->search.'%')->get();

        return response()->json([
            'members' => $members,
        ], 200);
    }

    public function searchAcademiesCourses(Academy $academy, Request $request)
    {
        $courses = $academy->courses()->where('name', 'like', '%'.$request->search.'%')->get();

        return response()->json([
            'courses' => CourseResource::collection($courses),
        ], 200);
    }

    public function searchAcademiesCourseStudents(Academy $academy, $courseId, Request $request)
    {
        $students = $academy->courses()->find($courseId)->students()->where('name', 'like', '%'.$request->search.'%')->get();

        return response()->json([
            'students' => $students,
        ], 200);
    }

    public function searchAcademiesCourseTeachers(Academy $academy, $courseId, Request $request)
    {
        $teachers = $academy->courses()->find($courseId)->teachers()->where('name', 'like', '%'.$request->search.'%')->get();

        return response()->json([
            'teachers' => $teachers,
        ], 200);
    }

    public function getMyAcademies()
    {
        try {
            // $academiesAuthMember = AcademyMember::where('user_id', auth()->id())->get('academy_id');

            return response()->json([
                'success' => true,
                'academies' => AcademyResource::collection(auth()->user()->academies()->paginate(10)),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 200);
        }
    }

    public function getAuthMemberedAcademies(User $user)
    {
        if (auth()->id() !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        try {
            // Get academies where user is a member with pending or approved status
            // Status: 1 = Pending, 2 = Approved, 3 = Rejected, 4 = Invited, 5 = Suspended
            $memberships = AcademyMember::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->whereIn('status', [1, 2, 'pending', 'accepted', 'approved']);
                })
                ->get(['academy_id', 'status', 'role']);

            $academyIds = $memberships->pluck('academy_id');

            // Get academies with pagination
            $academies = Academy::whereIn('id', $academyIds)->paginate(10);

            // Map member status to each academy
            $academiesWithStatus = $academies->getCollection()->map(function ($academy) use ($memberships) {
                $membership = $memberships->firstWhere('academy_id', $academy->id);
                $academy->memberStatus = $membership ? $membership->status : null;
                $academy->memberRole = $membership ? $membership->role : null;

                return $academy;
            });

            $academies->setCollection($academiesWithStatus);

            return response()->json([
                'success' => true,
                'academies' => AcademyResource::collection($academies),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 200);
        }
    }

    public function getAllAcademies()
    {
        try {
            return response()->json([
                'success' => true,
                'academies' => AcademyResource::collection(Academy::paginate(10)),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 200);
        }
    }

    /**
     * Update academy settings
     */
    public function updateSettings(Academy $academy, Request $request)
    {
        // Check if user is owner or has permission
        if ($academy->user_id !== auth()->id()) {
            $member = AcademyMember::where('academy_id', $academy->id)
                ->where('user_id', auth()->id())
                ->with('academyRole')
                ->first();

            if (! $member || ! $member->hasPermission('settings.manage')) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์แก้ไขการตั้งค่าโรงเรียน',
                ], 403);
            }
        }

        try {
            // Update basic info
            $academy->fill($request->only([
                'name', 'name_en', 'description', 'description_en',
                'email', 'phone', 'website', 'address', 'province', 'country',
            ]));

            // Generate slug if name changed
            if ($request->has('name') && $academy->isDirty('name')) {
                $academy->name_slug = \Str::slug($request->name);
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $avatar_name = uniqid().'.'.$avatar->getClientOriginalExtension();
                $avatar_url = Storage::disk('public')->putFileAs('images/academies/logos', $avatar, $avatar_name);
                $academy->logo = Storage::disk('public')->url($avatar_url);
            }

            // Handle cover upload
            if ($request->hasFile('cover')) {
                $cover = $request->file('cover');
                $cover_name = uniqid().'.'.$cover->getClientOriginalExtension();
                $cover_url = Storage::disk('public')->putFileAs('images/academies/covers', $cover, $cover_name);
                $academy->cover = Storage::disk('public')->url($cover_url);
            }

            $academy->save();

            // Update academy settings
            $setting = $academy->academySetting;
            if ($setting) {
                if ($request->has('privacy')) {
                    $setting->privacy = $request->privacy;
                }
                if ($request->has('join_mode')) {
                    $setting->auto_accept_members = $request->join_mode === 'open' ? 1 : 0;
                }
                if ($request->has('allow_student_registration')) {
                    $setting->allow_student_registration = $request->boolean('allow_student_registration');
                }
                if ($request->has('allow_parent_registration')) {
                    $setting->allow_parent_registration = $request->boolean('allow_parent_registration');
                }
                if ($request->has('show_member_list')) {
                    $setting->show_member_list = $request->boolean('show_member_list');
                }
                if ($request->has('show_course_list')) {
                    $setting->show_course_list = $request->boolean('show_course_list');
                }
                $setting->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'บันทึกการตั้งค่าเรียบร้อยแล้ว',
                'academy' => new AcademyResource($academy->fresh()),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
