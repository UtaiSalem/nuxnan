<?php

namespace App\Services\Import;

use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentImportBatch;
use App\Models\StudentImportRow;
use App\Models\User;
use App\Services\StudentCardSyncService;
use App\Services\StudentEnrollmentService;
use App\Services\StudentIntakeService;
use Illuminate\Support\Facades\DB;

class StudentRosterCommitService
{
    public function __construct(
        private readonly StudentIntakeService $intakeService,
        private readonly StudentEnrollmentService $enrollmentService,
        private readonly StudentCardSyncService $cardSyncService,
    ) {}

    /**
     * Process a single row and commit changes to DB.
     */
    public function processRow(StudentImportRow $row, User $operator): void
    {
        $data = $row->normalized_data;
        $action = $row->action;
        $academy = $row->batch->academy;
        $academicYear = $row->batch->academicYear;

        DB::transaction(function () use ($row, $data, $action, $academy, $academicYear, $operator) {
            $student = null;
            $classroom = null;

            if ($action === 'new_student') {
                // Prepare intake data structures
                $intakeData = [
                    'identity' => [
                        'student_id' => $data['identity']['student_id'],
                        'citizen_id' => $data['identity']['citizen_id'] ?? null,
                    ],
                    'personal' => [
                        'title_prefix_th' => $data['personal']['title_prefix_th'],
                        'first_name_th' => $data['personal']['first_name_th'],
                        'last_name_th' => $data['personal']['last_name_th'],
                        'middle_name_th' => $data['personal']['middle_name_th'] ?? null,
                        'title_prefix_en' => $data['personal']['title_prefix_en'] ?? null,
                        'first_name_en' => $data['personal']['first_name_en'] ?? null,
                        'last_name_en' => $data['personal']['last_name_en'] ?? null,
                        'middle_name_en' => $data['personal']['middle_name_en'] ?? null,
                        'date_of_birth' => $data['personal']['date_of_birth'] ?? null,
                        'gender' => $data['personal']['gender'] ?? null,
                        'nationality' => $data['personal']['nationality'] ?? 'ไทย',
                        'religion' => $data['personal']['religion'] ?? null,
                    ],
                    'admission' => [
                        'academic_year_id' => $academicYear->id,
                        'classroom_id' => $this->findClassroomId($academy->id, $academicYear->id, $data['admission']['grade_level'], $data['admission']['section']),
                        'student_number' => null,
                        'enrollment_date' => $data['admission']['enrollment_date'] ?? now()->toDateString(),
                    ],
                    'previous_school' => [
                        'name' => $data['academic_history']['previous_school'] ?? null,
                        'province' => $data['academic_history']['previous_school_province'] ?? null,
                        'grade_level' => $data['academic_history']['previous_grade'] ?? null,
                    ],
                    'guardians' => $this->formatGuardiansForIntake($data['guardians'] ?? []),
                    'account' => ['mode' => 'pending_activation'],
                ];

                $intakeResult = $this->intakeService->intake($academy, $intakeData, $operator);
                $student = $intakeResult['student'];
                $classroom = $intakeResult['enrollment']->classroom;
            } else {
                $student = Student::findOrFail($row->matched_student_id);
                $targetClassroomId = $this->findClassroomId($academy->id, $academicYear->id, $data['admission']['grade_level'], $data['admission']['section']);
                $classroom = Classroom::findOrFail($targetClassroomId);

                $this->updateIdentity($student, $data['identity']);

                if ($action === 'update_personal') {
                    $fields = [];
                    foreach ($row->diff_data as $field => $diff) {
                        $fields[$field] = $diff['after'];
                    }
                    $student->update($fields);
                }

                // Process enrollments
                $activeEnrollment = ClassroomStudent::where('student_id', $student->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('status', 'active')
                    ->first();

                if ($action === 'move_classroom' && $activeEnrollment) {
                    $fromClassroom = $activeEnrollment->classroom;
                    if ($fromClassroom->academic_year_id === $classroom->academic_year_id) {
                        $this->enrollmentService->transferStudent($student, $fromClassroom, $classroom, 'ย้ายห้อง', null, $operator->id);
                    } else {
                        $this->enrollmentService->promoteStudent($student, $fromClassroom, $classroom, 'เลื่อนชั้น', null, null, $operator->id);
                    }
                } elseif ($action === 'create_enrollment' || ($action === 'citizen_match' && ! $activeEnrollment)) {
                    $this->enrollmentService->enrollStudent($student, $classroom, null, null, $operator->id);
                }
            }

            if ($student && $classroom) {
                // Update Address details if available
                if (isset($data['address']['house_number']) || isset($data['address']['subdistrict'])) {
                    $student->addresses()->updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'address_type' => 'current',
                        ],
                        [
                            'house_number' => $data['address']['house_number'] ?? null,
                            'village_number' => $data['address']['village_number'] ?? null,
                            'alley' => $data['address']['alley'] ?? null,
                            'road' => $data['address']['road'] ?? null,
                            'subdistrict' => $data['address']['subdistrict'] ?? null,
                            'district' => $data['address']['district'] ?? null,
                            'province' => $data['address']['province'] ?? null,
                            'postal_code' => $data['address']['postal_code'] ?? null,
                            'is_current' => true,
                        ]
                    );
                }

                // Save/update student academic info (disability type & previous school name)
                $academicInfo = $student->academicInfos()
                    ->where('academic_year', $academicYear->name)
                    ->first();

                if ($academicInfo) {
                    $academicInfo->update([
                        'previous_school_name' => $data['academic_history']['previous_school'] ?? $academicInfo->previous_school_name,
                        'previous_school_province' => $data['academic_history']['previous_school_province'] ?? $academicInfo->previous_school_province,
                        'previous_grade_level' => $data['academic_history']['previous_grade'] ?? $academicInfo->previous_grade_level,
                        'disability_type' => $data['health']['disability_type'] ?? $academicInfo->disability_type,
                    ]);
                }

                // Update height and weight
                if (isset($data['health']['height_cm']) || isset($data['health']['weight_kg'])) {
                    $student->healthInfo()->updateOrCreate(
                        ['student_id' => $student->id],
                        [
                            'height_cm' => $data['health']['height_cm'] ?? null,
                            'weight_kg' => $data['health']['weight_kg'] ?? null,
                        ]
                    );
                }

            }

            $row->update([
                'status' => 'imported',
                'student_id' => $student?->id,
            ]);
        });
    }

    /**
     * Sync derived student cards once after every roster row has been committed.
     */
    public function syncCards(StudentImportBatch $batch, User $operator): array
    {
        return $this->cardSyncService->commitSync(
            $batch->academy,
            $batch->academicYear,
            $operator,
        );
    }

    private function updateIdentity(Student $student, array $identity): void
    {
        $updates = [];
        $citizenId = $identity['citizen_id'] ?? null;
        $studentCode = $identity['student_id'] ?? null;

        if ($citizenId && blank($student->citizen_id)) {
            $updates['citizen_id'] = $citizenId;
        }

        if ($studentCode && $studentCode !== $student->student_id && $citizenId === $student->citizen_id) {
            $updates['student_id'] = $studentCode;
        }

        if ($updates !== []) {
            $student->update($updates);
        }
    }

    private function findClassroomId(int $academyId, int $yearId, string $grade, string $section): int
    {
        $classroom = Classroom::where('academy_id', $academyId)
            ->where('academic_year_id', $yearId)
            ->where('grade_level', $grade)
            ->where('section', $section)
            ->first();

        if (! $classroom) {
            throw new \RuntimeException("Classroom {$grade}/{$section} not found.");
        }

        return $classroom->id;
    }

    private function formatGuardiansForIntake(array $guardians): array
    {
        $intakeGuardians = [];
        if (! empty($guardians['father']['first_name'])) {
            $intakeGuardians[] = [
                'guardian_type' => 'father',
                'first_name' => $guardians['father']['first_name'],
                'last_name' => $guardians['father']['last_name'] ?? '-',
                'status' => $guardians['father']['status'] ?? 'alive',
                'nationality' => $guardians['father']['nationality'] ?? 'ไทย',
                'citizen_id' => $guardians['father']['citizen_id'] ?? null,
            ];
        }
        if (! empty($guardians['mother']['first_name'])) {
            $intakeGuardians[] = [
                'guardian_type' => 'mother',
                'first_name' => $guardians['mother']['first_name'],
                'last_name' => $guardians['mother']['last_name'] ?? '-',
                'status' => $guardians['mother']['status'] ?? 'alive',
                'nationality' => $guardians['mother']['nationality'] ?? 'ไทย',
                'citizen_id' => $guardians['mother']['citizen_id'] ?? null,
            ];
        }
        if (! empty($guardians['primary']['first_name'])) {
            $intakeGuardians[] = [
                'guardian_type' => 'guardian',
                'first_name' => $guardians['primary']['first_name'],
                'last_name' => $guardians['primary']['last_name'] ?? '-',
                'relationship' => $guardians['primary']['relationship'] ?? null,
                'occupation' => $guardians['primary']['occupation'] ?? null,
                'citizen_id' => $guardians['primary']['citizen_id'] ?? null,
                'is_primary_contact' => true,
                'contacts' => ! empty($guardians['primary']['phone']) ? [
                    [
                        'contact_type' => 'mobile',
                        'contact_value' => $guardians['primary']['phone'],
                    ],
                ] : [],
            ];
        }

        return $intakeGuardians;
    }
}
