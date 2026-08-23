<?php

namespace App\Services\Course;

use App\Models\AcademyMember;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseGroupClassroom;
use App\Models\CourseGroupMember;
use App\Models\CourseMember;
use App\Models\User;
use App\Services\AcademyGroupPermissionAccessService;
use App\Services\LearnerIdentityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourseClassroomRosterService
{
    public function scopeFor(User $user, Course $course): string
    {
        if ($user->isSuperAdmin()) {
            return 'all';
        }

        $academy = $course->academy;
        if ($academy && $academy->isAdmin($user)) {
            return 'all';
        }

        if ($academy) {
            $member = AcademyMember::where('academy_id', $academy->id)
                ->where('user_id', $user->id)
                ->where('status', 2)
                ->first();

            if ($member && $member->academyRole && $member->academyRole->hasAnyPermission(['students.view'])) {
                return 'all';
            }
        }

        if ($academy && app(AcademyGroupPermissionAccessService::class)->hasAnyPermission($user, $academy, ['students.view'])) {
            return 'all';
        }

        return 'homeroom';
    }

    public function accessibleClassroomIds(User $user, Course $course): ?array
    {
        $scope = $this->scopeFor($user, $course);
        if ($scope === 'all') {
            return null;
        }

        $teacherIds = Classroom::where('academy_id', $course->academy_id)
            ->where('homeroom_teacher_id', $user->id)
            ->pluck('id')
            ->toArray();

        $coTeacherIds = DB::table('classroom_members')
            ->where('classroom_members.user_id', $user->id)
            ->where('classroom_members.is_active', 1)
            ->whereIn('classroom_members.role', ['teacher', 'co_teacher'])
            ->join('classrooms', 'classroom_members.classroom_id', '=', 'classrooms.id')
            ->where('classrooms.academy_id', $course->academy_id)
            ->pluck('classrooms.id')
            ->toArray();

        return array_unique(array_merge($teacherIds, $coTeacherIds));
    }

    public function assertCanUseClassrooms(User $user, Course $course, array $classroomIds): void
    {
        $classrooms = Classroom::whereIn('id', $classroomIds)->get();
        foreach ($classrooms as $classroom) {
            if ($classroom->academy_id !== $course->academy_id) {
                throw new AuthorizationException('ไม่มีสิทธิ์กับห้องที่เลือก');
            }
        }

        $accessibleIds = $this->accessibleClassroomIds($user, $course);
        if ($accessibleIds !== null) {
            foreach ($classroomIds as $classroomId) {
                if (! in_array($classroomId, $accessibleIds)) {
                    throw new AuthorizationException('ไม่มีสิทธิ์กับห้องที่เลือก');
                }
            }
        }
    }

    public function availableClassrooms(User $user, Course $course, ?int $academicYearId): array
    {
        if ($academicYearId === null) {
            $currentYear = DB::table('academic_years')
                ->where('academy_id', $course->academy_id)
                ->where('is_current', 1)
                ->first();
            $academicYearId = $currentYear ? $currentYear->id : null;
        }

        $accessibleIds = $this->accessibleClassroomIds($user, $course);

        $query = Classroom::where('academy_id', $course->academy_id)
            ->where('academic_year_id', $academicYearId);

        if ($accessibleIds !== null) {
            if (empty($accessibleIds)) {
                return [];
            }
            $query->whereIn('id', $accessibleIds);
        }

        $classrooms = $query->get();

        if ($classrooms->isEmpty()) {
            return [];
        }

        $classroomIds = $classrooms->pluck('id')->toArray();

        $studentsCount = DB::table('classroom_students')
            ->whereIn('classroom_id', $classroomIds)
            ->where('status', 'active')
            ->select('classroom_id', DB::raw('count(*) as count'))
            ->groupBy('classroom_id')
            ->pluck('count', 'classroom_id');

        $linkedGroups = DB::table('course_group_classrooms')
            ->join('course_groups', 'course_group_classrooms.course_group_id', '=', 'course_groups.id')
            ->where('course_groups.course_id', $course->id)
            ->whereIn('course_group_classrooms.classroom_id', $classroomIds)
            ->select('course_group_classrooms.classroom_id', 'course_groups.id', 'course_groups.name')
            ->get()
            ->keyBy('classroom_id');

        $result = [];
        foreach ($classrooms as $classroom) {
            $linked = $linkedGroups->get($classroom->id);
            $result[] = [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'grade_level' => $classroom->grade_level,
                'section' => $classroom->section,
                'academic_year_id' => $classroom->academic_year_id,
                'students_count' => $studentsCount->get($classroom->id, 0),
                'linked_group_id' => $linked ? $linked->id : null,
                'linked_group_name' => $linked ? $linked->name : null,
            ];
        }

        usort($result, function ($a, $b) {
            if ($a['grade_level'] !== $b['grade_level']) {
                return strcmp($a['grade_level'] ?? '', $b['grade_level'] ?? '');
            }

            return ((int) $a['section']) <=> ((int) $b['section']);
        });

        return $result;
    }

    public function roster(array $classroomIds): Collection
    {
        if (empty($classroomIds)) {
            return collect();
        }

        $students = DB::table('classroom_students')
            ->whereIn('classroom_id', $classroomIds)
            ->where('classroom_students.status', 'active')
            ->join('students', 'classroom_students.student_id', '=', 'students.id')
            ->leftJoin('users', 'students.user_id', '=', 'users.id')
            ->select(
                'classroom_students.classroom_id',
                'classroom_students.student_id',
                'students.user_id',
                'students.first_name_th',
                'students.last_name_th',
                'users.name as user_name',
                'classroom_students.student_number'
            )
            ->get();

        $roster = $students->map(function ($s) {
            $name = trim($s->first_name_th.' '.$s->last_name_th);
            if (empty($name)) {
                $name = $s->user_name;
            }

            return [
                'classroom_id' => $s->classroom_id,
                'student_id' => $s->student_id,
                'user_id' => $s->user_id,
                'name' => $name,
                'student_number' => $s->student_number,
            ];
        });

        return $roster->sortBy(function ($item) {
            return (int) $item['student_number'];
        })->values();
    }

    public function preview(User $user, Course $course, array $classroomIds, string $mode, ?string $groupName): array
    {
        $result = [];
        $classrooms = Classroom::whereIn('id', $classroomIds)->get()->keyBy('id');

        $rosterData = clone $this->roster($classroomIds);

        // Fetch existing members of this course
        $userIds = $rosterData->pluck('user_id')->filter()->toArray();
        $existingMembers = [];
        if (! empty($userIds)) {
            $existingMembers = CourseMember::where('course_id', $course->id)
                ->whereIn('user_id', $userIds)
                ->get()
                ->keyBy('user_id');
        }

        // Fetch groups
        $courseGroups = CourseGroup::where('course_id', $course->id)->get()->keyBy('id');

        $existingLinkedGroups = CourseGroupClassroom::whereIn('classroom_id', $classroomIds)
            ->whereIn('course_group_id', $courseGroups->keys())
            ->get()
            ->keyBy('classroom_id');

        $singleGroup = null;
        if ($mode === 'single_group') {
            $singleGroup = $courseGroups->firstWhere('name', $groupName);
        }

        if ($mode === 'per_classroom') {
            foreach ($classroomIds as $classroomId) {
                $classroom = $classrooms->get($classroomId);

                $targetGroup = null;
                $targetGroupExists = false;

                $linked = $existingLinkedGroups->get($classroomId);
                if ($linked) {
                    $targetGroup = $courseGroups->get($linked->course_group_id);
                    $targetGroupExists = true;
                } else {
                    $matchedGroup = $courseGroups->firstWhere('name', trim($classroom->name));
                    if ($matchedGroup) {
                        // Check if it's bound to any classroom already? (Rule says: "and not linked to any classroom")
                        $isLinked = CourseGroupClassroom::where('course_group_id', $matchedGroup->id)->exists();
                        if (! $isLinked) {
                            $targetGroup = $matchedGroup;
                            $targetGroupExists = true;
                        }
                    }
                }

                if (! $targetGroup) {
                    $targetGroup = (object) ['id' => null, 'name' => trim($classroom->name)];
                    $targetGroupExists = false;
                }

                $classroomRoster = $rosterData->where('classroom_id', $classroomId);
                $categorized = $this->categorizeRoster($classroomRoster, $existingMembers, $courseGroups, $targetGroupExists ? $targetGroup->id : null);

                $result[] = [
                    'classroom' => ['id' => $classroom->id, 'name' => $classroom->name],
                    'target_group' => ['id' => $targetGroup->id, 'name' => $targetGroup->name, 'exists' => $targetGroupExists],
                    'to_add' => $categorized['to_add'],
                    'already_member' => $categorized['already_member'],
                    'no_user_account' => $categorized['no_user_account'],
                    'moving_from_other_group' => $categorized['moving_from_other_group'],
                ];
            }
        } else {
            // single_group mode
            $targetGroupExists = $singleGroup !== null;
            $targetGroup = $targetGroupExists ? $singleGroup : (object) ['id' => null, 'name' => $groupName];

            $categorized = $this->categorizeRoster($rosterData, $existingMembers, $courseGroups, $targetGroupExists ? $targetGroup->id : null);

            $classroomInfo = $classrooms->map(function ($c) {
                return ['id' => $c->id, 'name' => $c->name];
            })->values()->toArray();

            $result[] = [
                'classroom' => $classroomInfo,
                'target_group' => ['id' => $targetGroup->id, 'name' => $targetGroup->name, 'exists' => $targetGroupExists],
                'to_add' => $categorized['to_add'],
                'already_member' => $categorized['already_member'],
                'no_user_account' => $categorized['no_user_account'],
                'moving_from_other_group' => $categorized['moving_from_other_group'],
            ];
        }

        return $result;
    }

    private function categorizeRoster($roster, $existingMembers, $courseGroups, $targetGroupId)
    {
        $to_add = [];
        $already_member = [];
        $no_user_account = [];
        $moving_from_other_group = [];

        foreach ($roster as $student) {
            if (! $student['user_id']) {
                $no_user_account[] = $student;

                continue;
            }

            $member = $existingMembers->get($student['user_id']);
            if ($member) {
                if ($member->role == 4) { // Admin/Teacher skip
                    continue;
                }

                if ($targetGroupId && $member->group_id == $targetGroupId) {
                    $already_member[] = $student;
                } elseif ($member->group_id === null) {
                    // เป็นสมาชิกรายวิชาอยู่แล้วแต่ยังไม่มีกลุ่ม — ไม่ใช่การย้าย
                    $to_add[] = $student;
                } else {
                    $fromGroup = $courseGroups->get($member->group_id);
                    $student['from_group'] = $fromGroup ? ['id' => $fromGroup->id, 'name' => $fromGroup->name] : null;
                    $moving_from_other_group[] = $student;
                }
            } else {
                $to_add[] = $student;
            }
        }

        return compact('to_add', 'already_member', 'no_user_account', 'moving_from_other_group');
    }

    public function apply(User $user, Course $course, array $classroomIds, string $mode, ?string $groupName): array
    {
        return DB::transaction(function () use ($user, $course, $classroomIds, $mode, $groupName) {
            $classrooms = Classroom::whereIn('id', $classroomIds)->get()->keyBy('id');
            $rosterData = clone $this->roster($classroomIds);

            $processedGroups = [];
            $allProcessedStudents = [];

            $identityService = app(LearnerIdentityService::class);

            if ($mode === 'per_classroom') {
                foreach ($classroomIds as $classroomId) {
                    $classroom = $classrooms->get($classroomId);

                    // a) Check existing link
                    $link = CourseGroupClassroom::where('classroom_id', $classroomId)
                        ->whereHas('courseGroup', function ($q) use ($course) {
                            $q->where('course_id', $course->id);
                        })->first();

                    if ($link) {
                        $group = $link->courseGroup;
                    } else {
                        // b) Exact name match
                        $group = CourseGroup::where('course_id', $course->id)
                            ->where('name', trim($classroom->name))
                            ->whereDoesntHave('classroomLinks')
                            ->first();

                        if (! $group) {
                            // c) Create new
                            $group = CourseGroup::create([
                                'course_id' => $course->id,
                                'user_id' => $user->id,
                                'name' => trim($classroom->name),
                                'color' => '#3B82F6',
                                'auto_accept_member' => 1,
                                'status' => 1,
                            ]);
                            $course->increment('groups');
                        }
                    }

                    CourseGroupClassroom::firstOrCreate([
                        'course_group_id' => $group->id,
                        'classroom_id' => $classroomId,
                    ], [
                        'academic_year_id' => $classroom->academic_year_id,
                        'created_by_user_id' => $user->id,
                    ]);

                    $processedGroups[$group->id] = $group;

                    $classroomRoster = $rosterData->where('classroom_id', $classroomId);
                    foreach ($classroomRoster as $student) {
                        if ($student['user_id']) {
                            $this->applyStudentToGroup($student, $group, $course, $identityService);
                        }
                    }

                    $group->classroom_synced_at = now();
                    $group->save();
                }
            } else {
                // single_group
                $group = CourseGroup::where('course_id', $course->id)
                    ->where('name', $groupName)
                    ->first();

                if (! $group) {
                    $group = CourseGroup::create([
                        'course_id' => $course->id,
                        'user_id' => $user->id,
                        'name' => $groupName,
                        'color' => '#3B82F6',
                        'auto_accept_member' => 1,
                        'status' => 1,
                    ]);
                    $course->increment('groups');
                }

                foreach ($classroomIds as $classroomId) {
                    $classroom = $classrooms->get($classroomId);
                    CourseGroupClassroom::firstOrCreate([
                        'course_group_id' => $group->id,
                        'classroom_id' => $classroomId,
                    ], [
                        'academic_year_id' => $classroom->academic_year_id,
                        'created_by_user_id' => $user->id,
                    ]);
                }

                $processedGroups[$group->id] = $group;

                foreach ($rosterData as $student) {
                    if ($student['user_id']) {
                        $this->applyStudentToGroup($student, $group, $course, $identityService);
                    }
                }

                $group->classroom_synced_at = now();
                $group->save();
            }

            return ['groups' => array_values($processedGroups)];
        });
    }

    private function applyStudentToGroup($student, CourseGroup $group, Course $course, LearnerIdentityService $identityService)
    {
        $userId = $student['user_id'];

        $member = CourseMember::firstOrNew([
            'course_id' => $course->id,
            'user_id' => $userId,
        ]);

        if ($member->role == 4 && $member->exists) {
            return;
        }

        if (! $member->exists) {
            $member->status = 1;
            $member->course_member_status = 1;
            $member->role = 1;
            $member->enrollment_date = now();
        }

        $member->group_id = $group->id;
        $member->group_member_status = 1;

        $identityService->autoPopulate($member);

        if ($student['student_number'] !== null && $student['student_number'] !== '') {
            $member->order_number = (int) $student['student_number'];
        }

        $member->save();

        CourseGroupMember::where('course_id', $course->id)
            ->where('user_id', $userId)
            ->where('group_id', '!=', $group->id)
            ->delete();

        CourseGroupMember::updateOrCreate([
            'group_id' => $group->id,
            'user_id' => $userId,
        ], [
            'course_id' => $course->id,
            'status' => 1,
            'request_status' => 'approved',
            'role' => 'member',
        ]);
    }

    public function syncPreview(User $user, Course $course, CourseGroup $group): array
    {
        $classroomIds = $group->classroomLinks()->pluck('classroom_id')->toArray();
        if (empty($classroomIds)) {
            abort(422, 'กลุ่มนี้ยังไม่ได้ผูกกับห้องเรียน');
        }

        $rosterData = $this->roster($classroomIds);

        $userIds = $rosterData->pluck('user_id')->filter()->toArray();
        $existingMembers = [];
        if (! empty($userIds)) {
            $existingMembers = CourseMember::where('course_id', $course->id)
                ->whereIn('user_id', $userIds)
                ->get()
                ->keyBy('user_id');
        }

        $courseGroups = CourseGroup::where('course_id', $course->id)->get()->keyBy('id');
        $categorized = $this->categorizeRoster($rosterData, $existingMembers, $courseGroups, $group->id);

        $currentMembers = CourseMember::where('course_id', $course->id)
            ->where('group_id', $group->id)
            ->where('role', '!=', 4)
            ->with('user')
            ->get();

        $missing = [];
        $unchanged_count = 0;

        foreach ($currentMembers as $member) {
            if (! in_array($member->user_id, $userIds)) {
                $missing[] = [
                    'course_member_id' => $member->id,
                    'user_id' => $member->user_id,
                    'name' => $member->member_name ?: ($member->user ? $member->user->name : ''),
                    'order_number' => $member->order_number,
                ];
            } else {
                $unchanged_count++;
            }
        }

        return [
            'classrooms' => Classroom::whereIn('id', $classroomIds)->select('id', 'name')->get()->toArray(),
            'to_add' => $categorized['to_add'],
            'missing' => $missing,
            'no_user_account' => $categorized['no_user_account'],
            'moving_from_other_group' => $categorized['moving_from_other_group'],
            'unchanged_count' => $unchanged_count,
            'classroom_synced_at' => $group->classroom_synced_at,
        ];
    }

    public function syncApply(User $user, Course $course, CourseGroup $group, array $detachMemberIds): array
    {
        return DB::transaction(function () use ($user, $course, $group, $detachMemberIds) {
            $classroomIds = $group->classroomLinks()->pluck('classroom_id')->toArray();
            if (empty($classroomIds)) {
                abort(422, 'กลุ่มนี้ยังไม่ได้ผูกกับห้องเรียน');
            }

            $rosterData = clone $this->roster($classroomIds);
            $identityService = app(LearnerIdentityService::class);

            foreach ($rosterData as $student) {
                if ($student['user_id']) {
                    $this->applyStudentToGroup($student, $group, $course, $identityService);
                }
            }

            $userIds = $rosterData->pluck('user_id')->filter()->toArray();

            if (! empty($detachMemberIds)) {
                $membersToDetach = CourseMember::where('course_id', $course->id)
                    ->where('group_id', $group->id)
                    ->where('role', '!=', 4)
                    ->whereIn('id', $detachMemberIds)
                    ->whereNotIn('user_id', $userIds)
                    ->get();

                foreach ($membersToDetach as $member) {
                    $member->group_id = null;
                    $member->group_member_status = 0;
                    $member->save();

                    CourseGroupMember::where('course_id', $course->id)
                        ->where('group_id', $group->id)
                        ->where('user_id', $member->user_id)
                        ->delete();
                }
            }

            $group->classroom_synced_at = now();
            $group->save();

            return $this->syncPreview($user, $course, $group);
        });
    }
}
