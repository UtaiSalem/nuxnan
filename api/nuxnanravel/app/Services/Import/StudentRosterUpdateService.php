<?php

namespace App\Services\Import;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentImportBatch;
use App\Models\StudentImportRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentRosterUpdateService
{
    /**
     * Preview matching data and save rows to DB.
     */
    public function preview(
        Academy $academy,
        AcademicYear $year,
        StudentImportBatch $batch,
        Collection $parsedRows
    ): void {
        $batch->update(['status' => 'validating']);
        $batch->rows()->delete();

        $seenCodes = [];
        $seenCitizens = [];

        foreach ($parsedRows as $row) {
            $rowNumber = $row['row_number'];
            $studentCode = $row['identity']['student_id'];
            $citizenId = $row['identity']['citizen_id'];

            $errors = [];
            $warnings = [];

            // Duplicate checks within import file itself
            if (isset($seenCodes[$studentCode])) {
                $errors[] = ['field' => 'student_code', 'message' => "Duplicate student code from row {$seenCodes[$studentCode]}."];
            } else {
                $seenCodes[$studentCode] = $rowNumber;
            }

            if ($citizenId && isset($seenCitizens[$citizenId])) {
                $errors[] = ['field' => 'citizen_id', 'message' => "Duplicate citizen ID from row {$seenCitizens[$citizenId]}."];
            } elseif ($citizenId) {
                $seenCitizens[$citizenId] = $rowNumber;
            }

            // Find student by student_code
            $studentByCode = Student::where('academy_id', $academy->id)
                ->where('student_id', $studentCode)
                ->first();

            // Find student by citizen_id
            $studentByCitizen = null;
            if ($citizenId) {
                $studentByCitizen = Student::where('academy_id', $academy->id)
                    ->where('citizen_id', $citizenId)
                    ->first();
            }

            $action = 'new_student';
            $matchedStudent = null;
            $identityDiff = [];

            if ($studentByCode && $studentByCitizen) {
                if ($studentByCode->id === $studentByCitizen->id) {
                    $action = 'matched';
                    $matchedStudent = $studentByCode;
                } else {
                    $action = 'conflict';
                    $errors[] = [
                        'field' => '_system',
                        'message' => "Conflict: student_code maps to student ID {$studentByCode->id} ({$studentByCode->full_name_th}) while citizen_id maps to student ID {$studentByCitizen->id} ({$studentByCitizen->full_name_th}).",
                    ];
                }
            } elseif ($studentByCode) {
                if ($citizenId) {
                    $action = 'update_identity';
                    $identityDiff['citizen_id'] = [
                        'before' => $studentByCode->citizen_id,
                        'after' => $citizenId,
                    ];
                } else {
                    $action = 'matched';
                }
                $matchedStudent = $studentByCode;
            } elseif ($studentByCitizen) {
                $action = 'citizen_match';
                $matchedStudent = $studentByCitizen;
                $identityDiff['student_id'] = [
                    'before' => $studentByCitizen->student_id,
                    'after' => $studentCode,
                ];
            } else {
                $action = 'new_student';
            }

            // Determine Enrollment action if matched
            $diffData = $identityDiff;
            if ($matchedStudent && $action !== 'conflict') {
                // Find classroom in target year
                $targetClassroom = Classroom::where('academy_id', $academy->id)
                    ->where('academic_year_id', $year->id)
                    ->where('grade_level', $row['admission']['grade_level'])
                    ->where('section', $row['admission']['section'])
                    ->first();

                if (! $targetClassroom) {
                    $errors[] = [
                        'field' => 'classroom',
                        'message' => "Classroom {$row['admission']['grade_level']}/{$row['admission']['section']} not found in target academic year.",
                    ];
                }

                // Check active enrollment in target year
                $activeEnrollment = ClassroomStudent::where('student_id', $matchedStudent->id)
                    ->where('academic_year_id', $year->id)
                    ->where('status', 'active')
                    ->first();

                if ($activeEnrollment) {
                    if ($targetClassroom && $activeEnrollment->classroom_id === $targetClassroom->id) {
                        $personalDiff = $this->calculatePersonalDiff($matchedStudent, $row);
                        if (! empty($personalDiff)) {
                            $action = 'update_personal';
                            $diffData = array_merge($diffData, $personalDiff);
                        } else {
                            $action = $identityDiff === [] ? 'unchanged' : 'update_identity';
                        }
                    } else {
                        $action = 'move_classroom';
                        $diffData = array_merge($diffData, [
                            'from_classroom' => $activeEnrollment->classroom?->name ?? 'Unknown',
                            'to_classroom' => "{$row['admission']['grade_level']}/{$row['admission']['section']}",
                        ]);
                    }
                } else {
                    $action = 'create_enrollment';
                }
            }

            if ($citizenId && ! $this->validThaiCitizenChecksum($citizenId)) {
                $warnings[] = ['field' => 'citizen_id', 'message' => 'Citizen ID checksum may be invalid.'];
            }

            StudentImportRow::create([
                'batch_id' => $batch->id,
                'row_number' => $rowNumber,
                'raw_data' => $row,
                'normalized_data' => $row,
                'status' => ! empty($errors) ? 'invalid' : (! empty($warnings) ? 'warning' : 'valid'),
                'action' => $action,
                'matched_student_id' => $matchedStudent?->id,
                'diff_data' => ! empty($diffData) ? $diffData : null,
                'errors' => ! empty($errors) ? $errors : null,
                'warnings' => ! empty($warnings) ? $warnings : null,
            ]);
        }

        $this->refreshBatchCounters($batch);
        $batch->update(['status' => 'validated']);
    }

    private function calculatePersonalDiff(Student $student, array $row): array
    {
        $diff = [];
        $personal = $row['personal'];

        $fieldsToCompare = [
            'title_prefix_th' => 'title_prefix_th',
            'first_name_th' => 'first_name_th',
            'last_name_th' => 'last_name_th',
            'middle_name_th' => 'middle_name_th',
            'title_prefix_en' => 'title_prefix_en',
            'first_name_en' => 'first_name_en',
            'last_name_en' => 'last_name_en',
            'middle_name_en' => 'middle_name_en',
            'nationality' => 'nationality',
            'religion' => 'religion',
        ];

        foreach ($fieldsToCompare as $rowKey => $dbKey) {
            $rowVal = trim((string) ($personal[$rowKey] ?? ''));
            $dbVal = trim((string) ($student->$dbKey ?? ''));
            if ($rowVal !== '' && $rowVal !== $dbVal) {
                $diff[$dbKey] = ['before' => $dbVal, 'after' => $rowVal];
            }
        }

        if (! empty($personal['date_of_birth'])) {
            $dbDob = $student->date_of_birth ? $student->date_of_birth->toDateString() : '';
            if ($personal['date_of_birth'] !== $dbDob) {
                $diff['date_of_birth'] = ['before' => $dbDob, 'after' => $personal['date_of_birth']];
            }
        }

        if (isset($personal['gender']) && $personal['gender'] !== null) {
            $dbGender = $student->gender;
            if ((int) $personal['gender'] !== (int) $dbGender) {
                $diff['gender'] = ['before' => $dbGender, 'after' => $personal['gender']];
            }
        }

        return $diff;
    }

    private function validThaiCitizenChecksum(string $id): bool
    {
        if (! preg_match('/^\d{13}$/', $id)) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += ((int) $id[$i]) * (13 - $i);
        }

        return (11 - ($sum % 11)) % 10 === (int) $id[12];
    }

    private function refreshBatchCounters(StudentImportBatch $batch): void
    {
        $counts = $batch->rows()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $batch->update([
            'total_rows' => $counts->sum(),
            'valid_rows' => ($counts['valid'] ?? 0) + ($counts['warning'] ?? 0) + ($counts['imported'] ?? 0),
            'invalid_rows' => $counts['invalid'] ?? 0,
            'imported_rows' => $counts['imported'] ?? 0,
            'skipped_rows' => $counts['skipped'] ?? 0,
        ]);
    }
}
