<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * ตรวจสิทธิ์การเข้าถึงพื้นที่ (scope) ของฟีด/ประกาศ/งาน/เอกสาร
 *
 * scope_type: academy | department | classroom
 * - department → ต้องเป็นสมาชิกฝ่าย (academy_group_members) หรือแอดมินโรงเรียน
 * - classroom  → ต้องเป็นครูประจำชั้น นักเรียนในห้อง (active) หรือแอดมินโรงเรียน
 */
class AcademyScopeAccessService
{
    /**
     * Validate the scope target belongs to the academy and the user may post
     * into it. Returns the normalized scope pair for persisting.
     *
     * @return array{scope_type: string, scope_id: int}
     */
    public function authorizePost(Academy $academy, ?User $user, ?string $scopeType, ?int $scopeId): array
    {
        $scopeType = $scopeType ?: 'academy';

        if ($scopeType === 'department') {
            $group = $this->resolveDepartment($academy, $scopeId);

            if (! $academy->isAdmin($user) && ! $this->isDepartmentMember($group, $user)) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์โพสต์ในฝ่ายงานนี้',
                ], 403));
            }

            return ['scope_type' => 'department', 'scope_id' => $group->id];
        }

        if ($scopeType === 'classroom') {
            $classroom = $this->resolveClassroom($academy, $scopeId);

            if (! $academy->isAdmin($user) && ! $this->isClassroomMember($academy, $classroom, $user)) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์โพสต์ในห้องเรียนนี้',
                ], 403));
            }

            return ['scope_type' => 'classroom', 'scope_id' => $classroom->id];
        }

        // academy scope: ผูกกับโรงเรียนปัจจุบันเสมอ ไม่รับ scope_id จากภายนอก
        return ['scope_type' => 'academy', 'scope_id' => $academy->id];
    }

    private function resolveDepartment(Academy $academy, ?int $scopeId): AcademyGroup
    {
        if (! $scopeId) {
            abort(response()->json([
                'success' => false,
                'message' => 'scope_id is required for department scope',
            ], 422));
        }

        $group = AcademyGroup::where('id', $scopeId)
            ->where('academy_id', $academy->id)
            ->where('type', 'department')
            ->first();

        if (! $group) {
            abort(response()->json([
                'success' => false,
                'message' => 'ไม่พบฝ่ายงานที่ระบุในสถาบันนี้',
            ], 404));
        }

        return $group;
    }

    private function resolveClassroom(Academy $academy, ?int $scopeId): Classroom
    {
        if (! $scopeId) {
            abort(response()->json([
                'success' => false,
                'message' => 'scope_id is required for classroom scope',
            ], 422));
        }

        $classroom = Classroom::where('id', $scopeId)
            ->where('academy_id', $academy->id)
            ->first();

        if (! $classroom) {
            abort(response()->json([
                'success' => false,
                'message' => 'ไม่พบห้องเรียนที่ระบุในสถาบันนี้',
            ], 404));
        }

        return $classroom;
    }

    private function isDepartmentMember(AcademyGroup $group, ?User $user): bool
    {
        return $user && DB::table('academy_group_members')
            ->where('academy_group_id', $group->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    private function isClassroomMember(Academy $academy, Classroom $classroom, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($classroom->homeroom_teacher_id === $user->id) {
            return true;
        }

        $student = Student::where('user_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->first();

        if (! $student) {
            return false;
        }

        return DB::table('classroom_students')
            ->where('classroom_id', $classroom->id)
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->exists();
    }
}
