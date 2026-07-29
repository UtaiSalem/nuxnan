<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentIntakeService
{
    public function __construct(
        private readonly StudentEnrollmentService $enrollmentService,
        private readonly AuditLogService $auditLogService,
        private readonly GuardianWriteService $guardianWriteService,
    ) {}

    /** @return array{student: Student, membership: AcademyMember, enrollment: ClassroomStudent} */
    public function intake(Academy $academy, array $data, User $operator): array
    {
        try {
            return DB::transaction(function () use ($academy, $data, $operator): array {
                $classroom = Classroom::query()->lockForUpdate()->findOrFail($data['admission']['classroom_id']);
                $this->assertClassroomContext($academy, $classroom, $data);
                $this->assertNoDuplicates($academy, $data);
                $this->assertClassroomAvailability($classroom, $data['admission']['student_number'] ?? null);

                $student = Student::create(array_merge($data['personal'], [
                    'academy_id' => $academy->id,
                    'user_id' => null,
                    'student_id' => $data['identity']['student_id'],
                    'citizen_id' => $data['identity']['citizen_id'] ?? null,
                    'status' => 'active',
                    'enrollment_date' => $data['admission']['enrollment_date'],
                    'account_status' => $data['account']['mode'],
                ]));

                $membership = AcademyMember::create([
                    'academy_id' => $academy->id,
                    'user_id' => null,
                    'student_id' => $student->id,
                    'member_code' => $student->student_id,
                    'status' => 2,
                    'role' => 'student',
                    'academy_role_id' => $this->studentRoleId($academy),
                    'enrollment_date' => $data['admission']['enrollment_date'],
                    'invited_by' => $operator->id,
                    'invited_at' => now(),
                ]);

                $enrollment = $this->enrollmentService->enrollStudent(
                    $student,
                    $classroom,
                    $data['admission']['student_number'] ?? null,
                    null,
                    $operator->id,
                );

                $enrollment->update(['enrolled_at' => $data['admission']['enrollment_date']]);
                $student->currentAcademicInfo?->update([
                    'enrollment_date' => $data['admission']['enrollment_date'],
                    'previous_school_name' => $data['previous_school']['name'] ?? null,
                    'previous_school_province' => $data['previous_school']['province'] ?? null,
                    'previous_grade_level' => $data['previous_school']['grade_level'] ?? null,
                ]);

                $guardians = collect($data['guardians'] ?? [])->map(function (array $guardian) use ($student, $academy, $operator): StudentGuardian {
                    $contacts = $guardian['contacts'] ?? [];
                    unset($guardian['contacts']);
                    $created = $this->guardianWriteService->create($student, array_merge($guardian, [
                        'academy_id' => $academy->id,
                        'student_code' => $student->student_id,
                        'status' => $guardian['status'] ?? 'alive',
                        'nationality' => $guardian['nationality'] ?? 'ไทย',
                        'contacts' => array_map(fn (array $contact): array => $contact + ['is_primary' => false, 'is_verified' => false], $contacts),
                    ]), 'intake', $operator->id);

                    return $created;
                });

                $this->auditLogService->logCustom('student_intake', $student, [
                    'academy_id' => $academy->id,
                    'membership_id' => $membership->id,
                    'enrollment_id' => $enrollment->id,
                    'account_status' => $student->account_status,
                    'operator_id' => $operator->id,
                    'guardian_count' => $guardians->count(),
                ], 'students');

                return [
                    'student' => $student->fresh(['currentAcademicInfo', 'guardians.contacts']),
                    'membership' => $membership->fresh('academyRole'),
                    'enrollment' => $enrollment->fresh(['academicYear', 'classroom', 'createdBy', 'student']),
                ];
            });
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages(['identity' => ['Student identity or enrollment data already exists.']]);
            }

            throw $exception;
        }
    }

    public function findDuplicates(Academy $academy, ?string $studentId, ?string $citizenId): array
    {
        return Student::query()
            ->where('academy_id', $academy->id)
            ->where(function ($query) use ($studentId, $citizenId): void {
                if ($studentId) {
                    $query->orWhere('student_id', $studentId);
                }
                if ($citizenId) {
                    $query->orWhere('citizen_id', $citizenId);
                }
            })
            ->get()
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'citizen_id' => $student->citizen_id,
                'first_name_th' => $student->first_name_th,
                'last_name_th' => $student->last_name_th,
                'status' => $student->status,
                'matched_fields' => array_values(array_filter([
                    $studentId && $student->student_id === $studentId ? 'student_id' : null,
                    $citizenId && $student->citizen_id === $citizenId ? 'citizen_id' : null,
                ])),
            ])->all();
    }

    private function assertClassroomContext(Academy $academy, Classroom $classroom, array $data): void
    {
        if ($classroom->academy_id !== $academy->id || $classroom->academic_year_id !== (int) $data['admission']['academic_year_id']) {
            throw ValidationException::withMessages(['admission.classroom_id' => ['The classroom does not belong to this academy and academic year.']]);
        }
    }

    private function assertNoDuplicates(Academy $academy, array $data): void
    {
        if ($this->findDuplicates($academy, $data['identity']['student_id'], $data['identity']['citizen_id'] ?? null) !== []) {
            throw ValidationException::withMessages(['identity' => ['Student code or citizen ID already exists in this academy.']]);
        }
    }

    private function assertClassroomAvailability(Classroom $classroom, ?int $studentNumber): void
    {
        $active = ClassroomStudent::query()->where('classroom_id', $classroom->id)->where('status', ClassroomStudent::STATUS_ACTIVE);

        if ($classroom->capacity !== null && $active->count() >= $classroom->capacity) {
            throw ValidationException::withMessages(['admission.classroom_id' => ['The classroom has reached its capacity.']]);
        }

        if ($studentNumber !== null && (clone $active)->where('student_number', $studentNumber)->exists()) {
            throw ValidationException::withMessages(['admission.student_number' => ['The student number is already in use in this classroom.']]);
        }
    }

    private function studentRoleId(Academy $academy): ?int
    {
        return AcademyRole::query()->forAcademy($academy->id)->where('name', 'student')->orderByRaw('academy_id IS NULL')->value('id');
    }
}
