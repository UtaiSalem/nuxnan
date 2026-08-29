<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Academy\AcademyMemberResource;
use App\Http\Resources\Learn\Academy\AcademyResource;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AttendanceDetail;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\CourseGrade;
use App\Models\CourseMember;
use App\Models\MemberActivityLog;
use App\Models\SemesterTranscript;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademyMemberController extends Controller
{
    public function updateEducationLevel(Academy $academy, AcademyMember $member, Request $request)
    {
        abort_if((int) $member->academy_id !== (int) $academy->id, 404);
        if ($member->student_id !== null) {
            return response()->json(['message' => 'Student education level is managed by academic information.'], 422);
        }
        $data = $request->validate(['education_level' => 'nullable|integer|in:1,2']);
        $old = $member->education_level;
        $member->update(['education_level' => $data['education_level'] ?? null]);
        MemberActivityLog::logActivity([
            'academy_id' => $academy->id, 'user_id' => $request->user()->id,
            'target_user_id' => $member->user_id, 'academy_member_id' => $member->id,
            'action' => MemberActivityLog::ACTION_PROFILE_UPDATE,
            'old_values' => ['education_level' => $old], 'new_values' => ['education_level' => $member->education_level],
            'description' => 'Updated staff education level',
        ]);

        return response()->json(['success' => true, 'data' => $member->fresh()]);
    }

    // index
    public function index(Academy $academy)
    {
        $courses = $academy->courses;
        $coursesresource = CourseResource::collection($courses);
        $isAcademyAdmin = $academy->user_id == auth()->id();

        return response()->json([
            // 'authMemberCourses' => $authMemberCourses,
            'allCourses' => $coursesresource,
            'courses' => $coursesresource,
            'authOwnerCourses' => CourseResource::collection(auth()->user()->courses),
            'authMemberCourses' => [],
            'academy' => new AcademyResource($academy),
            'isAcademyAdmin' => $isAcademyAdmin,
        ]);
    }

    public function storemember(Academy $academy)
    {
        // `join_mode` เป็นแหล่งความจริงเดียว — คอลัมน์ `auto_accept_members` ถูก drop แล้ว
        $joinMode = $academy->joinMode();

        if ($joinMode === 'invite_only') {
            return response()->json([
                'success' => false,
                'code' => 'invite_only',
                'msg' => 'โรงเรียนนี้เข้าร่วมได้เฉพาะผู้ที่ได้รับคำเชิญเท่านั้น',
                'message' => 'โรงเรียนนี้เข้าร่วมได้เฉพาะผู้ที่ได้รับคำเชิญเท่านั้น',
            ], 403);
        }

        $existingMember = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingMember) {
            return response()->json([
                'success' => false,
                'code' => 'already_requested',
                'msg' => 'คุณมีสถานะกับโรงเรียนนี้อยู่แล้ว',
                'message' => 'คุณมีสถานะกับโรงเรียนนี้อยู่แล้ว',
                'memberStatus' => $existingMember->status,
                'totalStudents' => $academy->total_students,
            ], 409);
        }

        if (auth()->user()->pp < $academy->membership_fees_points) {
            return response()->json([
                'success' => false,
                'msg' => 'แต้มสะสมไม่เพียงพอ กรุณาเติมแต้มสะสมก่อนสมัครสมาชิก',
            ], 201);
        }

        $status = $joinMode === 'open' ? 2 : 1;

        $newStatus = $academy->academyMembers()->create([
            'user_id' => auth()->id(),
            'status' => $status,
        ]);

        if ($status === 2) {
            $academy->increment('total_students');
        }

        MemberActivityLog::logActivity([
            'academy_id' => $academy->id,
            'academy_member_id' => $newStatus->id,
            'target_user_id' => auth()->id(),
            'action' => MemberActivityLog::ACTION_JOIN,
            'description' => $status === 2 ? 'เข้าร่วมโรงเรียน (โหมดเปิดรับสมัคร)' : 'ส่งคำขอเข้าร่วมโรงเรียน',
        ]);

        return response()->json([
            'success' => true,
            'memberStatus' => $newStatus->status,
            'totalStudents' => $academy->total_students,
        ], 200);
    }

    public function unmember(Academy $academy)
    {
        $auth_member = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', auth()->id())
            ->first();

        if (! $auth_member) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่ได้เป็นสมาชิกของโรงเรียนนี้',
            ], 404);
        }

        // Check if member is approved (status 2) before decrementing
        if ($auth_member->status == 2) {
            $academy->decrement('total_students');
        }

        // Only delete the current user's membership, not all members
        $auth_member->delete();
        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'academy_member_id' => $auth_member->id, 'target_user_id' => auth()->id(), 'action' => MemberActivityLog::ACTION_LEAVE, 'description' => 'ออกจากโรงเรียน']);

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการเป็นสมาชิกเรียบร้อยแล้ว',
        ], 200);
    }

    public function acceptmember(Academy $academy, AcademyMember $member)
    {
        if (! $this->canManageMembers($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์อนุมัติสมาชิก'], 403);
        }
        $member->update(['status' => 2]);
        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'academy_member_id' => $member->id, 'target_user_id' => $member->user_id, 'action' => MemberActivityLog::ACTION_APPROVE, 'description' => 'อนุมัติสมาชิก']);
        $academy->increment('total_students');

        return response()->json([
            'success' => true,
            'memberStatus' => $member->status,
            'totalStudents' => $academy->total_students,
        ], 200);
    }

    public function rejectmember(Academy $academy, AcademyMember $member)
    {
        if (! $this->canManageMembers($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์ปฏิเสธคำขอ'], 403);
        }
        $member->update(['status' => 3]);
        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'academy_member_id' => $member->id, 'target_user_id' => $member->user_id, 'action' => MemberActivityLog::ACTION_REJECT, 'description' => 'ปฏิเสธคำขอสมาชิก']);
        $academy->decrement('total_students');

        return response()->json([
            'success' => true,
            'memberStatus' => $member->status,
            'totalStudents' => $academy->total_students,
        ], 200);
    }

    public function memberstatus(Academy $academy)
    {
        $member = AcademyMember::where('academy_id', $academy->id)->where('user_id', auth()->id())->first();

        return response()->json([
            'success' => true,
            'memberStatus' => $member->status,
        ], 200);
    }

    public function memberlist(Academy $academy)
    {
        $perPage = request()->get('per_page', 20);
        $members = $academy->academyMembers()
            ->with(['user', 'student'])
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'members' => AcademyMemberResource::collection($members),
            'pagination' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ], 200);
    }

    public function membercount(Academy $academy)
    {
        $members = $academy->academyMembers()->count();

        return response()->json([
            'success' => true,
            'totalStudents' => $members,
        ], 200);
    }

    /**
     * Get available filter options for members (class levels, sections, etc.)
     * Uses classroom_students pivot as source of truth, with fallback to students table
     */
    public function getFilterOptions(Academy $academy)
    {
        $currentYearId = AcademicYear::query()
            ->where('academy_id', $academy->id)
            ->where('is_current', true)
            ->value('id');

        $hasActiveEnrollmentsInCurrentYear = $currentYearId
            ? ClassroomStudent::query()
                ->where('academy_id', $academy->id)
                ->where('academic_year_id', $currentYearId)
                ->where('status', ClassroomStudent::STATUS_ACTIVE)
                ->exists()
            : false;

        if ($hasActiveEnrollmentsInCurrentYear) {
            $currentEnrollmentQuery = \DB::table('classroom_students')
                ->join('classrooms', 'classroom_students.classroom_id', '=', 'classrooms.id')
                ->join('academy_members', function ($join) use ($academy) {
                    $join->on('academy_members.student_id', '=', 'classroom_students.student_id')
                        ->where('academy_members.academy_id', '=', $academy->id);
                })
                ->where('classroom_students.academy_id', $academy->id)
                ->where('classroom_students.academic_year_id', $currentYearId)
                ->where('classroom_students.status', ClassroomStudent::STATUS_ACTIVE)
                ->where('classrooms.is_active', true);

            $classLevels = (clone $currentEnrollmentQuery)
                ->select('classrooms.grade_level')
                ->distinct()
                ->orderBy('classrooms.grade_level')
                ->pluck('classrooms.grade_level')
                ->map(fn ($level) => ['value' => $level, 'label' => $level])
                ->values();

            $classSections = (clone $currentEnrollmentQuery)
                ->select('classrooms.section')
                ->distinct()
                ->orderBy('classrooms.section')
                ->pluck('classrooms.section')
                ->map(fn ($section) => ['value' => $section, 'label' => 'เธซเนเธญเธ '.$section])
                ->values();

            $classrooms = (clone $currentEnrollmentQuery)
                ->select(
                    'classrooms.id',
                    'classrooms.grade_level as class_level',
                    'classrooms.section as class_section',
                    'classrooms.name',
                    \DB::raw('COUNT(DISTINCT classroom_students.student_id) as student_count')
                )
                ->groupBy('classrooms.id', 'classrooms.grade_level', 'classrooms.section', 'classrooms.name')
                ->orderBy('classrooms.grade_level')
                ->orderBy('classrooms.section')
                ->get()
                ->map(function ($item) {
                    $label = $item->class_level;
                    if ($item->class_section) {
                        $label .= '/'.$item->class_section;
                    }

                    return [
                        'id' => $item->id,
                        'key' => $item->class_level.'|'.$item->class_section,
                        'class_level' => $item->class_level,
                        'class_section' => $item->class_section,
                        'label' => $item->name ?? $label,
                        'count' => $item->student_count,
                    ];
                })
                ->values();

            $genderCounts = \DB::table('students')
                ->join('academy_members', 'students.id', '=', 'academy_members.student_id')
                ->where('academy_members.academy_id', $academy->id)
                ->select(
                    'students.gender',
                    \DB::raw('COUNT(*) as count')
                )
                ->groupBy('students.gender')
                ->get()
                ->keyBy('gender');

            $genders = [
                ['value' => 1, 'label' => 'เธเธฒเธข', 'count' => $genderCounts->get(1)?->count ?? 0],
                ['value' => 0, 'label' => 'เธซเธเธดเธ', 'count' => $genderCounts->get(0)?->count ?? 0],
            ];

            return response()->json([
                'success' => true,
                'filters' => [
                    'class_levels' => $classLevels,
                    'class_sections' => $classSections,
                    'classrooms' => $classrooms,
                    'all_classrooms' => $classrooms,
                    'genders' => $genders,
                ],
            ], 200);
        }

        // Try to get data from classrooms table first (more reliable)
        $hasClassrooms = Classroom::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->exists();

        if ($hasClassrooms) {
            // Use classrooms as source for grade levels
            $classLevels = Classroom::where('academy_id', $academy->id)
                ->where('is_active', true)
                ->distinct()
                ->orderBy('grade_level')
                ->pluck('grade_level')
                ->map(fn ($level) => ['value' => $level, 'label' => $level])
                ->values();

            $classSections = Classroom::where('academy_id', $academy->id)
                ->where('is_active', true)
                ->distinct()
                ->orderBy('section')
                ->pluck('section')
                ->map(fn ($section) => ['value' => $section, 'label' => 'ห้อง '.$section])
                ->values();

            // Classroom summary with student count from pivot table
            $classrooms = \DB::table('classrooms')
                ->leftJoin('classroom_students', function ($join) {
                    $join->on('classrooms.id', '=', 'classroom_students.classroom_id')
                        ->where('classroom_students.status', '=', 'active');
                })
                ->where('classrooms.academy_id', $academy->id)
                ->where('classrooms.is_active', true)
                ->select(
                    'classrooms.id',
                    'classrooms.grade_level as class_level',
                    'classrooms.section as class_section',
                    'classrooms.name',
                    \DB::raw('COUNT(classroom_students.id) as student_count')
                )
                ->groupBy('classrooms.id', 'classrooms.grade_level', 'classrooms.section', 'classrooms.name')
                ->orderBy('classrooms.grade_level')
                ->orderBy('classrooms.section')
                ->get()
                ->map(function ($item) {
                    $label = $item->class_level;
                    if ($item->class_section) {
                        $label .= '/'.$item->class_section;
                    }

                    return [
                        'id' => $item->id,
                        'key' => $item->class_level.'|'.$item->class_section,
                        'class_level' => $item->class_level,
                        'class_section' => $item->class_section,
                        'label' => $item->name ?? $label,
                        'count' => $item->student_count,
                    ];
                });
        } else {
            // Fallback: use students.class_level + class_section directly
            $classLevels = Student::whereIn('id', function ($query) use ($academy) {
                $query->select('student_id')
                    ->from('academy_members')
                    ->where('academy_id', $academy->id)
                    ->whereNotNull('student_id');
            })
                ->whereNotNull('class_level')
                ->where('class_level', '!=', '')
                ->distinct()
                ->orderBy('class_level')
                ->pluck('class_level')
                ->map(fn ($level) => ['value' => $level, 'label' => $level])
                ->values();

            $classSections = Student::whereIn('id', function ($query) use ($academy) {
                $query->select('student_id')
                    ->from('academy_members')
                    ->where('academy_id', $academy->id)
                    ->whereNotNull('student_id');
            })
                ->whereNotNull('class_section')
                ->where('class_section', '!=', '')
                ->distinct()
                ->orderBy('class_section')
                ->pluck('class_section')
                ->map(fn ($section) => ['value' => $section, 'label' => 'ห้อง '.$section])
                ->values();

            $classrooms = \DB::table('students')
                ->join('academy_members', 'students.id', '=', 'academy_members.student_id')
                ->where('academy_members.academy_id', $academy->id)
                ->whereNotNull('students.class_level')
                ->select(
                    'students.class_level',
                    'students.class_section',
                    \DB::raw('COUNT(*) as student_count')
                )
                ->groupBy('students.class_level', 'students.class_section')
                ->orderBy('students.class_level')
                ->orderBy('students.class_section')
                ->get()
                ->map(function ($item) {
                    $label = $item->class_level;
                    if ($item->class_section) {
                        $label .= '/'.$item->class_section;
                    }

                    return [
                        'key' => $item->class_level.'|'.$item->class_section,
                        'class_level' => $item->class_level,
                        'class_section' => $item->class_section,
                        'label' => $label,
                        'count' => $item->student_count,
                    ];
                });
        }

        // Gender options
        $genderCounts = \DB::table('students')
            ->join('academy_members', 'students.id', '=', 'academy_members.student_id')
            ->where('academy_members.academy_id', $academy->id)
            ->select(
                'students.gender',
                \DB::raw('COUNT(*) as count')
            )
            ->groupBy('students.gender')
            ->get()
            ->keyBy('gender');

        $genders = [
            ['value' => 1, 'label' => 'ชาย', 'count' => $genderCounts->get(1)?->count ?? 0],
            ['value' => 0, 'label' => 'หญิง', 'count' => $genderCounts->get(0)?->count ?? 0],
        ];

        return response()->json([
            'success' => true,
            'filters' => [
                'class_levels' => $classLevels,
                'class_sections' => $classSections,
                'classrooms' => $classrooms,
                'all_classrooms' => $classrooms,
                'genders' => $genders,
            ],
        ], 200);
    }

    public function getAcademyMembers(Academy $academy)
    {
        $perPage = request()->get('per_page', 20);
        $members = $academy->academyMembers()
            ->with(['user', 'student', 'academyRole'])
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'members' => AcademyMemberResource::collection($members),
            'pagination' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ], 200);
    }

    /**
     * Invite a user to join the academy
     */
    public function inviteMember(Academy $academy, Request $request)
    {
        // Check if the current user is an admin of this academy
        if ($academy->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์เชิญสมาชิก',
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $userId = $request->user_id;

        // Check if user is already a member
        $existingMember = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', $userId)
            ->first();

        if ($existingMember) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้ใช้นี้เป็นสมาชิกหรือถูกเชิญอยู่แล้ว',
            ], 422);
        }

        // Create invitation (status 4 = invited)
        $invitation = $academy->academyMembers()->create([
            'user_id' => $userId,
            'status' => 4, // 4 = invited
            'invited_by' => auth()->id(),
        ]);
        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'academy_member_id' => $invitation->id, 'target_user_id' => $userId, 'action' => MemberActivityLog::ACTION_INVITE, 'description' => 'ส่งคำเชิญสมาชิก']);

        return response()->json([
            'success' => true,
            'message' => 'ส่งคำเชิญเรียบร้อยแล้ว',
            'invitation' => $invitation,
        ], 200);
    }

    /**
     * Accept an invitation to join academy
     */
    public function acceptInvitation(Academy $academy)
    {
        $invitation = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', auth()->id())
            ->where('status', 4) // invited status
            ->first();

        if (! $invitation) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบคำเชิญ',
            ], 404);
        }

        $invitation->update([
            'status' => 2, // member status
        ]);
        $academy->increment('total_students');
        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'academy_member_id' => $invitation->id, 'target_user_id' => auth()->id(), 'action' => MemberActivityLog::ACTION_ACCEPT_INVITE, 'description' => 'ยอมรับคำเชิญ']);

        return response()->json([
            'success' => true,
            'message' => 'ยอมรับคำเชิญเรียบร้อยแล้ว',
            'memberStatus' => 2,
            'totalStudents' => $academy->total_students,
        ], 200);
    }

    /**
     * Decline an invitation to join academy
     */
    public function declineInvitation(Academy $academy)
    {
        $invitation = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', auth()->id())
            ->where('status', 4) // invited status
            ->first();

        if (! $invitation) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบคำเชิญ',
            ], 404);
        }

        $invitation->delete();
        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'academy_member_id' => $invitation->id, 'target_user_id' => auth()->id(), 'action' => MemberActivityLog::ACTION_DECLINE_INVITE, 'description' => 'ปฏิเสธคำเชิญ']);

        return response()->json([
            'success' => true,
            'message' => 'ปฏิเสธคำเชิญเรียบร้อยแล้ว',
        ], 200);
    }

    /**
     * Get pending invitations for current user
     */
    public function getMyInvitations()
    {
        $invitations = AcademyMember::where('user_id', auth()->id())
            ->where('status', 4) // invited status
            ->with(['academy' => function ($query) {
                $query->select('id', 'name', 'logo', 'slogan', 'type');
            }])
            ->get();

        return response()->json([
            'success' => true,
            'invitations' => $invitations,
        ], 200);
    }

    /**
     * Get pending requests (for admin) - users who requested to join
     */
    public function getPendingRequests(Academy $academy)
    {
        // Owner, academy admins, or members holding members.manage may view join requests
        if (! $this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ดูข้อมูลนี้',
            ], 403);
        }

        $pendingRequests = AcademyMember::where('academy_id', $academy->id)
            ->where('status', 1) // pending status
            ->with('user:id,name,email,profile_photo_path,reference_code')
            ->latest('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'pendingRequests' => $pendingRequests,
        ], 200);
    }

    /**
     * Get academy members with search, filter, and pagination
     */
    public function searchMembers(Academy $academy, Request $request)
    {
        $query = AcademyMember::where('academy_id', $academy->id);
        $currentYearId = AcademicYear::query()
            ->where('academy_id', $academy->id)
            ->where('is_current', true)
            ->value('id');

        // Search by name, email, or member code
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('reference_code', 'LIKE', "%{$search}%");
                })
                    ->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('first_name_th', 'LIKE', "%{$search}%")
                            ->orWhere('last_name_th', 'LIKE', "%{$search}%")
                            ->orWhere('first_name_en', 'LIKE', "%{$search}%")
                            ->orWhere('last_name_en', 'LIKE', "%{$search}%")
                            ->orWhere('student_id', 'LIKE', "%{$search}%");
                    })
                    ->orWhere('member_code', 'LIKE', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        // Filter by role
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        if ($request->filled('roles')) {
            $roles = is_array($request->input('roles')) ? $request->input('roles') : [$request->input('roles')];
            $query->whereIn('role', array_filter($roles));
        }

        // Filter by academy_role_id
        if ($request->has('academy_role_id') && $request->academy_role_id) {
            $query->where('academy_role_id', $request->academy_role_id);
        }

        // Filter by tag
        if ($request->has('tag_id') && $request->tag_id) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('member_tags.id', $request->tag_id);
            });
        }

        // A classroom_key is authoritative when supplied: it is the FE's level|section composite.
        $classroomKey = $request->input('classroom_key');
        if ($classroomKey) {
            [$keyLevel, $keySection] = array_pad(explode('|', $classroomKey, 2), 2, null);
            $request->merge(['class_level' => $keyLevel, 'class_section' => $keySection]);
        }

        // Filter by the student's current classroom enrollment. The filter options
        // are sourced from classrooms, so do not rely only on stale student fields.
        if ($request->has('class_level') && $request->class_level) {
            $query->whereExists(function ($subquery) use ($academy, $request, $currentYearId) {
                $subquery->selectRaw('1')
                    ->from('classroom_students')
                    ->join('classrooms', 'classrooms.id', '=', 'classroom_students.classroom_id')
                    ->whereColumn('classroom_students.student_id', 'academy_members.student_id')
                    ->where('classroom_students.academy_id', $academy->id)
                    ->where('classroom_students.status', ClassroomStudent::STATUS_ACTIVE)
                    ->where('classrooms.is_active', true)
                    ->where('classrooms.grade_level', $request->class_level);
                if ($currentYearId) {
                    $subquery->where('classroom_students.academic_year_id', $currentYearId);
                }
            });
        }

        // Filter by the classroom section from the same enrollment source.
        if ($request->has('class_section') && $request->class_section) {
            $query->whereExists(function ($subquery) use ($academy, $request, $currentYearId) {
                $subquery->selectRaw('1')
                    ->from('classroom_students')
                    ->join('classrooms', 'classrooms.id', '=', 'classroom_students.classroom_id')
                    ->whereColumn('classroom_students.student_id', 'academy_members.student_id')
                    ->where('classroom_students.academy_id', $academy->id)
                    ->where('classroom_students.status', ClassroomStudent::STATUS_ACTIVE)
                    ->where('classrooms.is_active', true)
                    ->where('classrooms.section', $request->class_section);
                if ($currentYearId) {
                    $subquery->where('classroom_students.academic_year_id', $currentYearId);
                }
            });
        }

        // Filter by gender (เพศ)
        if ($request->has('gender') && $request->gender !== null && $request->gender !== '') {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('gender', $request->gender);
            });
        }

        // Filter by member_type (user หรือ student)
        if ($request->has('member_type') && $request->member_type) {
            if ($request->member_type === 'student') {
                $query->whereNotNull('student_id');
            } elseif ($request->member_type === 'user') {
                $query->whereNotNull('user_id')->whereNull('student_id');
            }
        }

        // Filter by membership date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Special sorting for student fields
        if (in_array($sortBy, ['class_level', 'class_section', 'student_id'])) {
            $query->leftJoin('students', 'academy_members.student_id', '=', 'students.id')
                ->select('academy_members.*')
                ->orderBy('students.'.$sortBy, $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $members = $query->with(['user', 'student', 'academyRole', 'inviter', 'tags'])->paginate($perPage);

        $memberRows = AcademyMemberResource::collection($members);
        if ($request->has('with_departments')) {
            $userIds = $members->getCollection()->pluck('user_id')->filter()->unique()->values();
            $membershipsByUser = DB::table('academy_group_members')
                ->join('academy_groups', 'academy_groups.id', '=', 'academy_group_members.academy_group_id')
                ->where('academy_groups.academy_id', $academy->id)
                ->where('academy_groups.type', 'department')
                ->whereIn('academy_group_members.user_id', $userIds)
                ->get([
                    'academy_group_members.user_id',
                    'academy_groups.id',
                    'academy_groups.name',
                    'academy_group_members.role',
                ])
                ->groupBy('user_id');

            $memberRows = collect($memberRows->resolve($request))->map(function (array $row) use ($membershipsByUser) {
                $row['department_memberships'] = $membershipsByUser
                    ->get($row['user_id'] ?? null, collect())
                    ->map(fn ($membership) => [
                        'id' => $membership->id,
                        'name' => $membership->name,
                        'role' => $membership->role,
                    ])
                    ->values()
                    ->all();

                return $row;
            })->values()->all();
        }

        return response()->json([
            'success' => true,
            'members' => $memberRows,
            'pagination' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ], 200);
    }

    public function invitationHistory(Academy $academy, Request $request)
    {
        if (! $this->canManageMembers($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์ดูประวัติการเชิญ'], 403);
        }

        $query = MemberActivityLog::forAcademy($academy->id)
            ->whereIn('action', [MemberActivityLog::ACTION_INVITE, MemberActivityLog::ACTION_ACCEPT_INVITE, MemberActivityLog::ACTION_DECLINE_INVITE])
            ->with(['user', 'targetUser']);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($builder) use ($q) {
                $builder->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                    ->orWhereHas('targetUser', fn ($user) => $user->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            });
        }
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $logs = $query->latest('created_at')->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(), 'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(), 'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Remove a member from the academy (admin only)
     */
    public function removeMember(Academy $academy, AcademyMember $member)
    {
        // Check if the current user has permission to remove members
        if (! $this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ลบสมาชิก',
            ], 403);
        }

        // Check if member belongs to this academy
        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้อยู่ในโรงเรียนนี้',
            ], 404);
        }

        // Cannot remove the owner
        if ($member->user_id === $academy->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบเจ้าของโรงเรียนได้',
            ], 403);
        }
        if ($member->user_id === auth()->id() && $academy->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถลบตัวเองได้'], 403);
        }

        // Decrement total_students if member was approved
        if ($member->status == 2) {
            $academy->decrement('total_students');
        }

        $memberName = $member->member_name;
        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'academy_member_id' => $member->id, 'target_user_id' => $member->user_id, 'action' => MemberActivityLog::ACTION_REMOVE, 'old_values' => ['role' => $member->role, 'status' => $member->status], 'description' => 'ลบสมาชิก']);
        $member->delete();

        return response()->json([
            'success' => true,
            'message' => "ลบสมาชิก {$memberName} เรียบร้อยแล้ว",
            'totalStudents' => $academy->total_students,
        ], 200);
    }

    /**
     * Suspend a member
     */
    public function suspendMember(Academy $academy, AcademyMember $member, Request $request)
    {
        if (! $this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ระงับสมาชิก',
            ], 403);
        }

        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้อยู่ในโรงเรียนนี้',
            ], 404);
        }

        // Cannot suspend the owner
        if ($member->user_id === $academy->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถระงับเจ้าของโรงเรียนได้',
            ], 403);
        }
        if ($member->user_id === auth()->id() && $academy->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถระงับตัวเองได้'], 403);
        }

        $previousStatus = $member->status;
        $member->update([
            'status' => 5, // 5 = suspended
            'note_comment' => $request->get('reason', 'ถูกระงับโดยผู้ดูแล'),
        ]);

        // Decrement if previously approved
        if ($previousStatus == 2) {
            $academy->decrement('total_students');
        }
        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'academy_member_id' => $member->id, 'target_user_id' => $member->user_id, 'action' => MemberActivityLog::ACTION_SUSPEND, 'new_values' => ['reason' => $member->note_comment], 'description' => 'ระงับสมาชิก']);

        return response()->json([
            'success' => true,
            'message' => 'ระงับสมาชิกเรียบร้อยแล้ว',
            'member' => new AcademyMemberResource($member->load(['user', 'student', 'academyRole'])),
        ], 200);
    }

    /**
     * Unsuspend (reactivate) a member
     */
    public function unsuspendMember(Academy $academy, AcademyMember $member)
    {
        if (! $this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ยกเลิกการระงับสมาชิก',
            ], 403);
        }

        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้อยู่ในโรงเรียนนี้',
            ], 404);
        }

        if ($member->status !== 5) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้ถูกระงับ',
            ], 422);
        }

        $member->update([
            'status' => 2, // 2 = approved member
            'note_comment' => null,
        ]);

        $academy->increment('total_students');
        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'academy_member_id' => $member->id, 'target_user_id' => $member->user_id, 'action' => MemberActivityLog::ACTION_UNSUSPEND, 'description' => 'ยกเลิกการระงับสมาชิก']);

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการระงับสมาชิกเรียบร้อยแล้ว',
            'member' => new AcademyMemberResource($member->load(['user', 'student', 'academyRole'])),
            'totalStudents' => $academy->total_students,
        ], 200);
    }

    /**
     * Update member identity fields at Academy level
     */
    public function updateIdentity(Academy $academy, AcademyMember $member, Request $request)
    {
        // Check if current user is the member themselves OR has permission to manage members
        $user = auth()->user();
        $isOwnerOfMemberRecord = $member->user_id === $user->id;

        if (! $isOwnerOfMemberRecord && ! $this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์แก้ไขข้อมูลนี้',
            ], 403);
        }

        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้อยู่ในโรงเรียนนี้',
            ], 404);
        }
        if ($member->user_id === $academy->user_id && auth()->id() !== $academy->user_id) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถแก้ไขข้อมูลเจ้าของโรงเรียนได้'], 403);
        }

        $validated = $request->validate([
            'member_code' => 'nullable|string|max:50',
        ]);

        $oldValues = $member->only(array_keys($validated));
        $member->update($validated);
        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'academy_member_id' => $member->id, 'target_user_id' => $member->user_id, 'action' => MemberActivityLog::ACTION_PROFILE_UPDATE, 'old_values' => $oldValues, 'new_values' => $member->only(array_keys($validated)), 'description' => 'อัปเดตข้อมูลสมาชิก']);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทข้อมูลเรียบร้อยแล้ว',
            'member' => new AcademyMemberResource($member->load(['user', 'student'])),
        ]);
    }

    /**
     * Update member details (note, enrollment date, etc.)
     */
    public function updateMember(Academy $academy, AcademyMember $member, Request $request)
    {
        if (! $this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์แก้ไขข้อมูลสมาชิก',
            ], 403);
        }

        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้อยู่ในโรงเรียนนี้',
            ], 404);
        }
        if ($member->user_id === $academy->user_id && auth()->id() !== $academy->user_id) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถแก้ไขข้อมูลเจ้าของโรงเรียนได้'], 403);
        }

        $validated = $request->validate([
            'member_code' => 'nullable|string|max:50',
            'note_comment' => 'nullable|string|max:500',
            'enrollment_date' => 'nullable|date',
            'graduation_date' => 'nullable|date',
            'additional_info' => 'nullable|string',
        ]);

        $oldValues = $member->only(array_keys($validated));
        $member->update($validated);
        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'academy_member_id' => $member->id, 'target_user_id' => $member->user_id, 'action' => MemberActivityLog::ACTION_PROFILE_UPDATE, 'old_values' => $oldValues, 'new_values' => $member->only(array_keys($validated)), 'description' => 'อัปเดตข้อมูลสมาชิก']);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทข้อมูลสมาชิกเรียบร้อยแล้ว',
            'member' => new AcademyMemberResource($member->load(['user', 'student', 'academyRole'])),
        ], 200);
    }

    /**
     * Get member statistics for the academy
     */
    public function getMemberStats(Academy $academy)
    {
        $stats = [
            'total' => AcademyMember::where('academy_id', $academy->id)->count(),
            'approved' => AcademyMember::where('academy_id', $academy->id)->where('status', 2)->count(),
            'pending' => AcademyMember::where('academy_id', $academy->id)->where('status', 1)->count(),
            'invited' => AcademyMember::where('academy_id', $academy->id)->where('status', 4)->count(),
            'rejected' => AcademyMember::where('academy_id', $academy->id)->where('status', 3)->count(),
            'suspended' => AcademyMember::where('academy_id', $academy->id)->where('status', 5)->count(),
        ];

        // Get role distribution
        $roleDistribution = AcademyMember::where('academy_id', $academy->id)
            ->where('status', 2)
            ->selectRaw('role, count(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'role_distribution' => $roleDistribution,
        ], 200);
    }

    /**
     * Check if current user can manage members in the academy
     */
    private function canManageMembers(Academy $academy): bool
    {
        $user = auth()->user();

        // Owner can manage everything
        if ($academy->user_id === $user->id) {
            return true;
        }

        // Admin can manage members
        if ($academy->academyAdmins()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Check member permission
        $member = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', $user->id)
            ->with('academyRole')
            ->first();

        if ($member && $member->academyRole) {
            return $member->hasPermission('members.manage');
        }

        return false;
    }

    /**
     * Get detailed member profile
     */
    public function getMemberProfile(Academy $academy, AcademyMember $member)
    {
        // Check if member belongs to this academy
        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้อยู่ในโรงเรียนนี้',
            ], 404);
        }

        // Load relationships
        $member->load(['user', 'student', 'academyRole', 'inviter']);

        // Get course statistics
        $enrolledCoursesCount = 0;
        $completedCoursesCount = 0;

        if ($member->user_id) {
            $enrolledCoursesCount = CourseMember::whereHas('course', function ($q) use ($academy) {
                $q->where('academy_id', $academy->id);
            })->where('user_id', $member->user_id)->where('status', 2)->count();

            $completedCoursesCount = CourseMember::whereHas('course', function ($q) use ($academy) {
                $q->where('academy_id', $academy->id);
            })->where('user_id', $member->user_id)->where('is_completed', true)->count();
        }

        $memberData = (new AcademyMemberResource($member))->toArray(request());
        $memberData['enrolled_courses_count'] = $enrolledCoursesCount;
        $memberData['completed_courses_count'] = $completedCoursesCount;
        // Calculate GPA from SemesterTranscript or CourseGrade
        $gpa = null;
        if ($member->user_id) {
            // Try to get latest published/completed semester transcript
            $latestTranscript = SemesterTranscript::where('student_id', $member->student_id?->id ?? 0)
                ->where('academy_id', $academy->id)
                ->whereIn('status', [SemesterTranscript::STATUS_PUBLISHED, SemesterTranscript::STATUS_APPROVED])
                ->orderBy('semester_id', 'desc')
                ->first();

            if ($latestTranscript && $latestTranscript->gpa !== null) {
                $gpa = (float) $latestTranscript->gpa;
            } elseif ($member->user_id) {
                // Fallback: Calculate from CourseGrade
                $courseGrades = CourseGrade::where('student_id', $member->student_id?->id ?? 0)
                    ->where('status', CourseGrade::STATUS_COMPLETED)
                    ->where('is_published', true)
                    ->get();

                if ($courseGrades->isNotEmpty()) {
                    $totalGradePoints = 0;
                    $totalCredits = 0;
                    foreach ($courseGrades as $grade) {
                        $credits = $grade->course?->credit_units ?? 1;
                        if ($grade->grade_points !== null && $grade->grade_points >= 1) {
                            $totalGradePoints += $grade->grade_points * $credits;
                            $totalCredits += $credits;
                        }
                    }
                    $gpa = $totalCredits > 0 ? round($totalGradePoints / $totalCredits, 2) : 0;
                }
            }
        }
        $memberData['gpa'] = $gpa;

        // Calculate attendance rate from attendance records
        $attendanceRate = null;
        if ($member->user_id && $member->student_id) {
            $attendanceDetails = AttendanceDetail::whereHas('courseMember', function ($q) use ($academy, $member) {
                $q->where('academy_id', $academy->id)
                    ->where('user_id', $member->user_id);
            })
                ->where('course_member_id', '!=', null)
                ->get();

            if ($attendanceDetails->isNotEmpty()) {
                $totalSessions = $attendanceDetails->count();
                $attendedSessions = $attendanceDetails->where('status', 'present')->count();
                $attendanceRate = $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100, 2) : 0;
            }
        }
        $memberData['attendance_rate'] = $attendanceRate;

        return response()->json([
            'success' => true,
            'member' => $memberData,
        ], 200);
    }

    /**
     * Get member's enrolled courses
     */
    public function getMemberCourses(Academy $academy, AcademyMember $member)
    {
        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้อยู่ในโรงเรียนนี้',
            ], 404);
        }

        $courses = [];

        if ($member->user_id) {
            $courseMembers = CourseMember::with('course')
                ->whereHas('course', function ($q) use ($academy) {
                    $q->where('academy_id', $academy->id);
                })
                ->where('user_id', $member->user_id)
                ->where('status', 2)
                ->get();

            foreach ($courseMembers as $cm) {
                if ($cm->course) {
                    $courses[] = [
                        'id' => $cm->course->id,
                        'name' => $cm->course->name,
                        'cover_image' => $cm->course->cover_image,
                        'progress' => $cm->progress ?? 0,
                        'status' => $cm->is_completed ? 'completed' : 'in_progress',
                        'enrolled_at' => $cm->created_at?->format('Y-m-d'),
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'courses' => $courses,
        ], 200);
    }

    /**
     * Get member's activity log
     */
    public function getMemberActivity(Academy $academy, AcademyMember $member)
    {
        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'สมาชิกไม่ได้อยู่ในโรงเรียนนี้',
            ], 404);
        }

        // For now, return basic activities based on member history
        // In future, this can be extended with a proper activity log table
        $activities = [];

        // Add enrollment activity
        $activities[] = [
            'id' => 1,
            'description' => 'เข้าร่วมเป็นสมาชิกโรงเรียน',
            'icon' => 'fluent:person-add-24-regular',
            'created_at' => $member->created_at?->toISOString(),
        ];

        // Add role assignment if has role
        if ($member->academy_role_id) {
            $activities[] = [
                'id' => 2,
                'description' => 'ได้รับบทบาท '.($member->academyRole->display_name_th ?? 'สมาชิก'),
                'icon' => 'fluent:person-tag-24-regular',
                'created_at' => $member->updated_at?->toISOString(),
            ];
        }

        return response()->json([
            'success' => true,
            'activities' => $activities,
        ], 200);
    }

    /**
     * Bulk invite members to the academy
     * Accepts user IDs and/or email addresses
     */
    public function bulkInviteMembers(Academy $academy, Request $request)
    {
        // Check permission
        if (! $this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์เชิญสมาชิก',
            ], 403);
        }

        $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'emails' => 'nullable|array',
            'emails.*' => 'email',
            'role' => 'nullable|string|in:student,parent,teacher,staff,admin',
        ]);

        $userIds = $request->user_ids ?? [];
        $emails = $request->emails ?? [];
        $role = $request->role ?? 'student';

        $invitedCount = 0;
        $skippedCount = 0;
        $errors = [];

        // Invite by user IDs
        foreach ($userIds as $userId) {
            $existingMember = AcademyMember::where('academy_id', $academy->id)
                ->where('user_id', $userId)
                ->first();

            if ($existingMember) {
                $skippedCount++;

                continue;
            }

            AcademyMember::create([
                'academy_id' => $academy->id,
                'user_id' => $userId,
                'role' => $role,
                'status' => 4, // invited
                'invited_by' => auth()->id(),
                'invited_at' => now(),
            ]);
            $invitedCount++;
        }

        // Invite by emails (create invitation or find existing user)
        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();

            if ($user) {
                // User exists, check if already member
                $existingMember = AcademyMember::where('academy_id', $academy->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existingMember) {
                    $skippedCount++;

                    continue;
                }

                AcademyMember::create([
                    'academy_id' => $academy->id,
                    'user_id' => $user->id,
                    'role' => $role,
                    'status' => 4, // invited
                    'invited_by' => auth()->id(),
                    'invited_at' => now(),
                ]);
                $invitedCount++;
            } else {
                // TODO: Send email invitation to non-existing user
                // For now, just skip and report
                $errors[] = "อีเมล {$email} ไม่พบในระบบ";
                $skippedCount++;
            }
        }

        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'action' => MemberActivityLog::ACTION_INVITE, 'new_values' => ['invited_count' => $invitedCount, 'skipped_count' => $skippedCount], 'description' => 'ส่งคำเชิญสมาชิกแบบกลุ่ม']);

        return response()->json([
            'success' => true,
            'message' => "ส่งคำเชิญเรียบร้อย {$invitedCount} คน",
            'invited_count' => $invitedCount,
            'skipped_count' => $skippedCount,
            'errors' => $errors,
        ], 200);
    }

    /**
     * Import members from CSV file
     * CSV should have columns: email (required), role (optional), member_code (optional)
     */
    public function importMembersFromCsv(Academy $academy, Request $request)
    {
        // Check permission
        if (! $this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์นำเข้าสมาชิก',
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120', // 5MB max
            'default_role' => 'nullable|string|in:student,parent,teacher,staff,admin',
            'auto_approve' => 'nullable|boolean',
        ]);

        $file = $request->file('file');
        $defaultRole = $request->get('default_role', 'student');
        $autoApprove = $request->boolean('auto_approve', false);

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rows = [];

        // Parse CSV
        $handle = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle);

        // Normalize headers (lowercase, trim)
        $headers = array_map(function ($h) {
            return strtolower(trim($h));
        }, $headers);

        // Find required column indexes
        $emailIndex = array_search('email', $headers);
        $roleIndex = array_search('role', $headers);
        $memberCodeIndex = array_search('member_code', $headers);
        $referenceCodeIndex = array_search('reference_code', $headers);

        if ($emailIndex === false && $referenceCodeIndex === false) {
            fclose($handle);

            return response()->json([
                'success' => false,
                'message' => 'ไฟล์ CSV ต้องมีคอลัมน์ email หรือ reference_code',
            ], 422);
        }

        $lineNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            $email = $emailIndex !== false ? trim($row[$emailIndex] ?? '') : null;
            $referenceCode = $referenceCodeIndex !== false ? trim($row[$referenceCodeIndex] ?? '') : null;
            $role = $roleIndex !== false ? trim($row[$roleIndex] ?? '') : $defaultRole;
            $memberCode = $memberCodeIndex !== false ? trim($row[$memberCodeIndex] ?? '') : null;

            // Validate role
            if (! in_array($role, ['student', 'parent', 'teacher', 'staff', 'admin'])) {
                $role = $defaultRole;
            }

            // Find user by email or reference_code
            $user = null;
            if ($email) {
                $user = User::where('email', $email)->first();
            }
            if (! $user && $referenceCode) {
                $user = User::where('reference_code', $referenceCode)->first();
            }

            if (! $user) {
                $identifier = $email ?: $referenceCode;
                $errors[] = "บรรทัด {$lineNumber}: ไม่พบผู้ใช้ {$identifier}";
                $skipped++;

                continue;
            }

            // Check if already a member
            $existingMember = AcademyMember::where('academy_id', $academy->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingMember) {
                $skipped++;

                continue;
            }

            // Create member
            $status = $autoApprove ? 2 : 4; // 2 = approved, 4 = invited

            AcademyMember::create([
                'academy_id' => $academy->id,
                'user_id' => $user->id,
                'role' => $role,
                'member_code' => $memberCode,
                'status' => $status,
                'invited_by' => auth()->id(),
                'invited_at' => now(),
            ]);

            if ($autoApprove) {
                $academy->increment('total_students');
            }

            $imported++;
        }

        fclose($handle);
        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'action' => MemberActivityLog::ACTION_BULK_ACTION, 'new_values' => ['imported' => $imported, 'skipped' => $skipped, 'errors' => count($errors)], 'description' => 'นำเข้าสมาชิกแบบกลุ่ม']);

        $statusText = $autoApprove ? 'เพิ่มสมาชิก' : 'ส่งคำเชิญ';

        return response()->json([
            'success' => true,
            'message' => "{$statusText}เรียบร้อย {$imported} คน",
            'imported_count' => $imported,
            'skipped_count' => $skipped,
            'errors' => $errors,
            'total_students' => $academy->total_students,
        ], 200);
    }

    /**
     * Bulk action on members
     * Actions: approve, reject, suspend, unsuspend, remove
     */
    public function bulkAction(Academy $academy, Request $request)
    {
        // Check permission
        if (! $this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ดำเนินการนี้',
            ], 403);
        }

        $request->validate([
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'integer|exists:academy_members,id',
            'action' => 'required|string|in:approve,reject,suspend,unsuspend,remove',
            'reason' => 'nullable|string|max:500',
        ]);

        $memberIds = $request->member_ids;
        $action = $request->action;
        $reason = $request->get('reason', '');

        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $skipped = [];

        foreach ($memberIds as $memberId) {
            $member = AcademyMember::where('id', $memberId)
                ->where('academy_id', $academy->id)
                ->first();

            if (! $member) {
                $failedCount++;
                $errors[] = "ไม่พบสมาชิก ID: {$memberId}";

                continue;
            }

            // Cannot modify academy owner
            if ($member->user_id === $academy->user_id) {
                $failedCount++;
                $skipped[] = ['member_id' => $member->id, 'reason' => 'owner'];
                $errors[] = 'ไม่สามารถดำเนินการกับเจ้าของโรงเรียนได้';

                continue;
            }
            if (in_array($action, ['suspend', 'remove'], true) && $member->user_id === auth()->id()) {
                $failedCount++;
                $skipped[] = ['member_id' => $member->id, 'reason' => 'self'];

                continue;
            }

            try {
                switch ($action) {
                    case 'approve':
                        if ($member->status !== 2) {
                            $previousStatus = $member->status;
                            $member->update(['status' => 2]);
                            if ($previousStatus !== 2) {
                                $academy->increment('total_students');
                            }
                            $successCount++;
                        }
                        break;

                    case 'reject':
                        if ($member->status !== 3) {
                            if ($member->status === 2) {
                                $academy->decrement('total_students');
                            }
                            $member->update([
                                'status' => 3,
                                'note_comment' => $reason ?: 'ถูกปฏิเสธโดยผู้ดูแล',
                            ]);
                            $successCount++;
                        }
                        break;

                    case 'suspend':
                        if ($member->status !== 5) {
                            if ($member->status === 2) {
                                $academy->decrement('total_students');
                            }
                            $member->update([
                                'status' => 5,
                                'note_comment' => $reason ?: 'ถูกระงับโดยผู้ดูแล',
                            ]);
                            $successCount++;
                        }
                        break;

                    case 'unsuspend':
                        if ($member->status === 5) {
                            $member->update([
                                'status' => 2,
                                'note_comment' => null,
                            ]);
                            $academy->increment('total_students');
                            $successCount++;
                        }
                        break;

                    case 'remove':
                        if ($member->status === 2) {
                            $academy->decrement('total_students');
                        }
                        $member->delete();
                        $successCount++;
                        break;
                }
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "เกิดข้อผิดพลาดกับสมาชิก ID: {$memberId}";
            }
        }

        $actionLabels = [
            'approve' => 'อนุมัติ',
            'reject' => 'ปฏิเสธ',
            'suspend' => 'ระงับ',
            'unsuspend' => 'ยกเลิกการระงับ',
            'remove' => 'ลบ',
        ];

        MemberActivityLog::logActivity(['academy_id' => $academy->id, 'action' => MemberActivityLog::ACTION_BULK_ACTION, 'new_values' => ['action' => $action, 'target_ids' => $memberIds, 'success_count' => $successCount, 'skipped' => $skipped], 'description' => 'ดำเนินการสมาชิกแบบกลุ่ม']);

        return response()->json([
            'success' => $successCount > 0,
            'message' => "{$actionLabels[$action]}สำเร็จ {$successCount} รายการ".($failedCount > 0 ? ", ล้มเหลว {$failedCount} รายการ" : ''),
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
            'skipped' => $skipped,
            'total_students' => $academy->fresh()->total_students,
        ], 200);
    }

    /**
     * Export selected members to CSV
     */
    public function exportSelectedMembers(Academy $academy, Request $request)
    {
        // Check permission
        if (! $this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ส่งออกข้อมูลสมาชิก',
            ], 403);
        }

        $request->validate([
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer',
        ]);

        $memberIds = $request->member_ids;

        $query = AcademyMember::where('academy_id', $academy->id)
            ->with(['user:id,email,name,reference_code', 'student', 'academyRole']);

        // If specific IDs provided, filter by them
        if (! empty($memberIds)) {
            $query->whereIn('id', $memberIds);
        }

        $members = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="academy_members_'.date('Y-m-d_His').'.csv"',
        ];

        $callback = function () use ($members) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV header
            fputcsv($file, [
                'รหัสสมาชิก',
                'ชื่อ',
                'อีเมล',
                'รหัสอ้างอิง',
                'บทบาท',
                'สถานะ',
                'วันที่สมัคร',
            ]);

            $statusLabels = [
                1 => 'รอการอนุมัติ',
                2 => 'สมาชิก',
                3 => 'ถูกปฏิเสธ',
                4 => 'ได้รับเชิญ',
                5 => 'ถูกระงับ',
            ];

            foreach ($members as $member) {
                fputcsv($file, [
                    $member->member_code ?? '-',
                    $member->member_name ?? '-',
                    $member->user->email ?? '-',
                    $member->user->reference_code ?? '-',
                    $member->academyRole->display_name_th ?? $member->role ?? 'สมาชิก',
                    $statusLabels[$member->status] ?? 'ไม่ทราบ',
                    $member->created_at?->format('Y-m-d') ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export members to CSV
     */
    public function exportMembersToCsv(Academy $academy)
    {
        // Check permission
        if (! $this->canManageMembers($academy)) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ส่งออกข้อมูลสมาชิก',
            ], 403);
        }

        $members = AcademyMember::where('academy_id', $academy->id)
            ->with(['user:id,email,name,reference_code', 'student'])
            ->where('status', 2) // Only approved members
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="academy_members_'.$academy->id.'.csv"',
        ];

        $callback = function () use ($members) {
            $file = fopen('php://output', 'w');

            // CSV header
            fputcsv($file, [
                'member_code',
                'name',
                'email',
                'reference_code',
                'role',
                'status',
                'enrollment_date',
            ]);

            foreach ($members as $member) {
                fputcsv($file, [
                    $member->member_code ?? '',
                    $member->member_name ?? '',
                    $member->user->email ?? '',
                    $member->user->reference_code ?? '',
                    $member->role ?? 'student',
                    $member->status_text ?? '',
                    $member->enrollment_date ?? $member->created_at?->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
