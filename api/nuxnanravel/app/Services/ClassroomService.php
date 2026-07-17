<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\ClassroomMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
     * Resolve academic_year_id and academic_year string on the data array.
     */
    private function resolveAcademicYear(int $academyId, array &$data): void
    {
        if (! empty($data['academic_year_id'])) {
            $ay = DB::table('academic_years')->where('academy_id', $academyId)->where('id', $data['academic_year_id'])->first();
            if ($ay) {
                $data['academic_year'] = $ay->name;
            }
        } elseif (! empty($data['academic_year'])) {
            $academicYearName = $data['academic_year'];
            $ay = DB::table('academic_years')
                ->where('academy_id', $academyId)
                ->where('name', $academicYearName)
                ->first();

            if (! $ay) {
                $yearInt = intval($academicYearName);
                if ($yearInt > 0) {
                    $adYear = $yearInt - 543;
                    $startDate = "{$adYear}-05-16";
                    $endDate = ($adYear + 1).'-03-31';
                } else {
                    $startDate = date('Y-05-16');
                    $endDate = date('Y-03-31', strtotime('+1 year'));
                }

                $academicYearId = DB::table('academic_years')->insertGetId([
                    'academy_id' => $academyId,
                    'name' => $academicYearName,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_current' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $data['academic_year_id'] = $academicYearId;
            } else {
                $data['academic_year_id'] = $ay->id;
            }
        }
    }

    /**
     * Check if a classroom already exists in the academy.
     */
    private function checkUniqueness(int $academyId, array $data, ?int $ignoreId = null): void
    {
        $query = Classroom::where('academy_id', $academyId)
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('grade_level', $data['grade_level'])
            ->where('section', $data['section']);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'grade_level' => ["มีห้อง {$data['grade_level']}/{$data['section']} ปีการศึกษานี้อยู่แล้ว"],
            ]);
        }
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

        $this->resolveAcademicYear($academyId, $data);
        $data['name'] = $data['name'] ?? "{$data['grade_level']}/{$data['section']}";

        $this->checkUniqueness($academyId, $data);

        try {
            return DB::transaction(function () use ($data) {
                $classroom = Classroom::create($data);

                // If homeroom_teacher_id is set, auto-add as teacher member
                if (! empty($data['homeroom_teacher_id'])) {
                    $this->addTeacherMember($classroom, $data['homeroom_teacher_id']);
                }

                return $classroom->load(['academicYear', 'homeroomTeacher']);
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw ValidationException::withMessages([
                    'grade_level' => ["มีห้อง {$data['grade_level']}/{$data['section']} ปีการศึกษานี้อยู่แล้ว"],
                ]);
            }
            throw $e;
        }
    }

    /**
     * Update a classroom.
     */
    public function updateClassroom(Classroom $classroom, array $data): Classroom
    {
        // Copy existing values if they are not passed to resolveAcademicYear
        $mergedData = array_merge([
            'academic_year_id' => $classroom->academic_year_id,
            'academic_year' => $classroom->academic_year,
            'grade_level' => $classroom->grade_level,
            'section' => $classroom->section,
        ], $data);

        $this->resolveAcademicYear($classroom->academy_id, $mergedData);
        $this->checkUniqueness($classroom->academy_id, $mergedData, $classroom->id);

        try {
            return DB::transaction(function () use ($classroom, $mergedData, $data) {
                $previousTeacherId = $classroom->homeroom_teacher_id;
                $newTeacherId = array_key_exists('homeroom_teacher_id', $data)
                    ? $data['homeroom_teacher_id']
                    : $previousTeacherId;

                $classroom->update($mergedData);

                if (array_key_exists('homeroom_teacher_id', $data)
                    && $newTeacherId !== null
                    && (int) $previousTeacherId !== (int) $newTeacherId) {
                    $this->addTeacherMember($classroom, (int) $newTeacherId);
                }

                return $classroom->fresh(['academicYear', 'homeroomTeacher']);
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw ValidationException::withMessages([
                    'grade_level' => ["มีห้อง {$mergedData['grade_level']}/{$mergedData['section']} ปีการศึกษานี้อยู่แล้ว"],
                ]);
            }
            throw $e;
        }
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
     *
     * If the user is already a member with a different role (e.g. co_teacher,
     * observer), upgrade the row to teacher so classroom_members stays in sync
     * with classrooms.homeroom_teacher_id.
     */
    private function addTeacherMember(Classroom $classroom, int $userId): void
    {
        $existing = ClassroomMember::where('classroom_id', $classroom->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            if ($existing->role !== ClassroomMember::ROLE_TEACHER) {
                $existing->update(['role' => ClassroomMember::ROLE_TEACHER]);
            }

            return;
        }

        ClassroomMember::create([
            'classroom_id' => $classroom->id,
            'user_id' => $userId,
            'role' => ClassroomMember::ROLE_TEACHER,
            'join_method' => ClassroomMember::JOIN_ADMIN,
            'added_by' => Auth::id(),
        ]);
    }
}
