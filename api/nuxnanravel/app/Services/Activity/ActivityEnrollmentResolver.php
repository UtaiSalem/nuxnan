<?php

namespace App\Services\Activity;

use App\Models\AcademicYear;
use App\Models\ActivityEnrollment;
use App\Models\SchoolEvent;
use Illuminate\Database\UniqueConstraintViolationException;

class ActivityEnrollmentResolver
{
    public function currentTerm(SchoolEvent $event): array
    {
        $year = AcademicYear::where('academy_id', $event->academy_id)
            ->where('is_current', true)
            ->first();
        $academicYear = (string) ($year?->name ?? (now()->year + 543));
        $semester = $year?->semesters()->where('is_current', true)->value('semester_number');

        // These columns must never be NULL because the unique index ignores NULL duplicates.
        return ['semester' => (string) ($semester ?? '-'), 'academic_year' => $academicYear];
    }

    public function find(SchoolEvent $event, int $userId): ?ActivityEnrollment
    {
        return ActivityEnrollment::where('event_id', $event->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();
    }

    public function resolveOrCreate(SchoolEvent $event, int $userId): ?ActivityEnrollment
    {
        if ($enrollment = $this->find($event, $userId)) {
            return $enrollment;
        }

        if (! app(EventAudienceResolver::class)->isMember($event, $userId)) {
            return null;
        }

        $term = $this->currentTerm($event);

        try {
            $enrollment = ActivityEnrollment::firstOrCreate(
                ['user_id' => $userId, 'event_id' => $event->id, ...$term],
                ['status' => 'active'],
            );
        } catch (UniqueConstraintViolationException) {
            // Lost a race with a concurrent scan of the same QR — take the winner's row.
            return $this->find($event, $userId);
        }

        // firstOrCreate matches on the unique-index columns, which exclude status, so it can hand
        // back a row someone dropped from the activity. Refuse rather than silently reinstating them.
        return $enrollment->status === 'active' ? $enrollment : null;
    }
}
