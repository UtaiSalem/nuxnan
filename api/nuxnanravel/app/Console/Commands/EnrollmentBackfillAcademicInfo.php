<?php

namespace App\Console\Commands;

use App\Models\ClassroomStudent;
use App\Models\StudentAcademicInfo;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EnrollmentBackfillAcademicInfo extends Command
{
    protected $signature = 'enrollment:backfill-academic-info
                            {--year= : Limit to a specific academic year name (for example 2568)}
                            {--academy= : Limit to a specific academy id}
                            {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Backfill missing student_academic_info rows from classroom enrollment history';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $summary = [
            'processed' => 0,
            'created' => 0,
            'patched_null_year' => 0,
            'enriched_existing' => 0,
            'skipped_existing' => 0,
            'skipped_missing_links' => 0,
            'skipped_current_conflict' => 0,
        ];

        $this->info('Starting enrollment academic info backfill...');
        if ($dryRun) {
            $this->warn('Dry run mode enabled. No database writes will be performed.');
        }

        $query = ClassroomStudent::query()
            ->with(['student', 'classroom.academicYear'])
            ->orderBy('id');

        if ($academyId = $this->option('academy')) {
            $query->where('academy_id', $academyId);
        }

        if ($year = $this->option('year')) {
            $query->whereHas('academicYear', function (Builder $builder) use ($year) {
                $builder->where('name', $year);
            });
        }

        $query->chunkById(200, function (Collection $enrollments) use (&$summary, $dryRun) {
            foreach ($enrollments as $enrollment) {
                $summary['processed']++;

                $student = $enrollment->student;
                $classroom = $enrollment->classroom;
                $yearName = $enrollment->academicYear?->name ?: $classroom?->academic_year;

                if (! $student || ! $classroom || ! $yearName) {
                    $summary['skipped_missing_links']++;

                    continue;
                }

                $existing = StudentAcademicInfo::where('student_id', $student->id)
                    ->where('academic_year', $yearName)
                    ->first();

                if ($existing) {
                    $shouldMarkCurrent = $enrollment->status === ClassroomStudent::STATUS_ACTIVE;
                    $markCurrent = $shouldMarkCurrent && ! $this->studentHasOtherCurrentRow($student->id, $existing->id);

                    if ($shouldMarkCurrent && ! $markCurrent) {
                        $summary['skipped_current_conflict']++;
                    }

                    $changes = $this->missingValuePatch(
                        $existing,
                        $this->buildPayload($enrollment, $yearName, $markCurrent),
                        false
                    );

                    if ($changes === []) {
                        $summary['skipped_existing']++;

                        continue;
                    }

                    if (! $dryRun) {
                        $existing->update($changes);
                    }

                    $summary['enriched_existing']++;

                    continue;
                }

                $candidate = StudentAcademicInfo::where('student_id', $student->id)
                    ->whereNull('academic_year')
                    ->where(function (Builder $builder) use ($classroom) {
                        $builder->where('classroom_id', $classroom->id)
                            ->orWhere(function (Builder $fallback) use ($classroom) {
                                $fallback->whereNull('classroom_id')
                                    ->where('current_grade', $classroom->grade_level)
                                    ->where('current_class', $classroom->section);
                            });
                    })
                    ->orderByDesc('is_current')
                    ->orderByDesc('id')
                    ->first();

                $shouldMarkCurrent = $enrollment->status === ClassroomStudent::STATUS_ACTIVE;
                $markCurrent = $shouldMarkCurrent && ! $this->studentHasOtherCurrentRow($student->id, $candidate?->id);

                if ($shouldMarkCurrent && ! $markCurrent) {
                    $summary['skipped_current_conflict']++;
                }

                $payload = $this->buildPayload($enrollment, $yearName, $markCurrent);

                if ($candidate) {
                    $changes = $this->missingValuePatch($candidate, $payload, true);

                    if ($changes !== [] && ! $dryRun) {
                        $candidate->update($changes);
                    }

                    $summary['patched_null_year']++;

                    continue;
                }

                if (! $dryRun) {
                    StudentAcademicInfo::create($payload);
                }

                $summary['created']++;
            }
        });

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            collect($summary)->map(fn ($count, $label) => [$label, (string) $count])->all()
        );

        $this->info('Enrollment academic info backfill completed.');

        return self::SUCCESS;
    }

    private function buildPayload(ClassroomStudent $enrollment, string $yearName, bool $markCurrent): array
    {
        $classroom = $enrollment->classroom;
        $student = $enrollment->student;

        return [
            'academy_id' => $enrollment->academy_id ?: $classroom?->academy_id,
            'student_id' => $enrollment->student_id,
            'classroom_id' => $enrollment->classroom_id,
            'current_grade' => $classroom?->grade_level,
            'education_level' => $classroom?->grade_level
                ? StudentAcademicInfo::getEducationLevelFromString($classroom->grade_level)
                : null,
            'current_class' => $classroom?->section,
            'classroom_full' => $classroom?->display_name,
            'academic_year' => $yearName,
            'semester' => $classroom?->semester ?? StudentAcademicInfo::getCurrentSemester(),
            'enrollment_date' => $enrollment->enrolled_at ?: $student?->enrollment_date,
            'graduation_date' => $enrollment->status === ClassroomStudent::STATUS_GRADUATED ? $enrollment->left_at : null,
            'study_status' => $this->mapStudyStatus($enrollment->status),
            'is_current' => $markCurrent,
            'transfer_reason' => $this->mapTransferReason($enrollment),
        ];
    }

    private function mapStudyStatus(string $status): string
    {
        return match ($status) {
            ClassroomStudent::STATUS_GRADUATED => StudentAcademicInfo::STATUS_GRADUATED,
            ClassroomStudent::STATUS_DROPPED => StudentAcademicInfo::STATUS_DROPPED,
            default => StudentAcademicInfo::STATUS_STUDYING,
        };
    }

    private function mapTransferReason(ClassroomStudent $enrollment): ?string
    {
        return in_array($enrollment->status, [
            ClassroomStudent::STATUS_TRANSFERRED,
            ClassroomStudent::STATUS_DROPPED,
        ], true) ? $enrollment->leave_reason : null;
    }

    private function studentHasOtherCurrentRow(int $studentId, ?int $ignoreId = null): bool
    {
        return StudentAcademicInfo::where('student_id', $studentId)
            ->where('is_current', true)
            ->when($ignoreId, fn (Builder $builder) => $builder->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function missingValuePatch(StudentAcademicInfo $record, array $payload, bool $forceYear): array
    {
        $changes = [];

        foreach ($payload as $field => $value) {
            $current = $record->{$field};

            if ($field === 'academic_year' && $forceYear && empty($current) && ! empty($value)) {
                $changes[$field] = $value;

                continue;
            }

            if ($field === 'is_current') {
                if (! $record->is_current && $value === true) {
                    $changes[$field] = true;
                }

                continue;
            }

            if (($current === null || $current === '') && $value !== null && $value !== '') {
                $changes[$field] = $value;
            }
        }

        return $changes;
    }
}
