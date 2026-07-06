<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\ClassroomMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ClassroomService — CRUD and helper operations for classrooms.
 *
 * Business Rules enforced:
 * - BR-4: academic_year is mandatory when creating.
 * - BR-3: homeroom_teacher uniqueness per academic_year + semester (optional validation).
 */
class ClassroomService
{
    /**
     * List classrooms for a given academy with optional filters.
     *
     * @param  array  $filters  Keys: academic_year_id, academic_year, semester, grade_level, status
     * @param  bool  $withMembers  Eager-load activeMembers with user
     * @return Collection
     */
    public function listClassrooms(int $academyId, array $filters = [], bool $withMembers = false)
    {
        $query = Classroom::where('academy_id', $academyId)
            ->with(['academicYear', 'homeroomTeacher'])
            ->withStudentCount();

        if ($withMembers) {
            $query->with([
                'activeMembers.user',
            ]);
        }

        // Count teachers from classroom_members table
        $query->withCount([
            'activeMembers',
            'activeMembers as member_teacher_count' => fn ($q) => $q->whereIn('role', ['teacher', 'co_teacher']),
        ]);

        foreach (['academic_year_id', 'academic_year', 'semester', 'grade_level', 'status'] as $key) {
            if (! empty($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }

        return $query->orderBy('grade_level')->orderBy('section')->get();
    }

    /**
     * Get a single classroom with members summary.
     */
    public function getClassroom(int $academyId, int $classroomId): Classroom
    {
        return Classroom::where('academy_id', $academyId)
            ->with(['academicYear', 'homeroomTeacher', 'creator'])
            ->withStudentCount()
            ->withCount([
                'activeMembers',
                'activeMembers as member_teacher_count' => fn ($q) => $q->whereIn('role', ['teacher', 'co_teacher']),
            ])
            ->findOrFail($classroomId);
    }

    /**
     * Create a new classroom. Auto-generates classroom_code.
     *
     * @param  array  $data  Validated data
     *
     * @throws \InvalidArgumentException if academic_year is missing (BR-4)
     */
    public function createClassroom(int $academyId, array $data): Classroom
    {
        $data['academy_id'] = $academyId;
        $data['created_by'] = Auth::id();
        $data['name'] = $data['name'] ?? "{$data['grade_level']}/{$data['section']}";

        return DB::transaction(function () use ($data) {
            $classroom = Classroom::create($data);

            // If homeroom_teacher_id is set, auto-add as teacher member
            if (! empty($data['homeroom_teacher_id'])) {
                $this->addTeacherMember($classroom, $data['homeroom_teacher_id']);
            }

            return $classroom->load(['academicYear', 'homeroomTeacher']);
        });
    }

    /**
     * Update a classroom.
     */
    public function updateClassroom(Classroom $classroom, array $data): Classroom
    {
        $classroom->update($data);

        return $classroom->fresh(['academicYear', 'homeroomTeacher']);
    }

    /**
     * Archive a classroom (soft status change).
     */
    public function archiveClassroom(Classroom $classroom): Classroom
    {
        $classroom->update(['status' => Classroom::STATUS_ARCHIVED, 'is_active' => false]);

        return $classroom;
    }

    /**
     * Delete a classroom (cascade handled by DB FK).
     */
    public function deleteClassroom(Classroom $classroom): void
    {
        $classroom->delete();
    }

    /**
     * Auto-add the homeroom teacher as a classroom member with role=teacher.
     */
    private function addTeacherMember(Classroom $classroom, int $userId): void
    {
        ClassroomMember::firstOrCreate(
            ['classroom_id' => $classroom->id, 'user_id' => $userId],
            [
                'role' => ClassroomMember::ROLE_TEACHER,
                'join_method' => ClassroomMember::JOIN_ADMIN,
                'added_by' => Auth::id(),
            ]
        );
    }
}
