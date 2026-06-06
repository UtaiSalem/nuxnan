<?php

namespace App\Enums;

/**
 * CourseLifecycleState
 *
 * Derived state of a Course at a point in time. Computed from `courses.status`,
 * `courses.finalization_status`, and `courses.end_date`. Not stored as a column.
 */
enum CourseLifecycleState: string
{
    case Draft = 'draft';
    case Active = 'active';
    case EnrollmentClosed = 'enrollment_closed';
    case Grading = 'grading';
    case Published = 'published';
    case Finalized = 'finalized';
    case Archived = 'archived';

    public function isEnrollmentOpen(): bool
    {
        return $this === self::Active;
    }

    public function isLearningActive(): bool
    {
        return in_array($this, [self::Active, self::EnrollmentClosed], true);
    }

    public function isPostEnd(): bool
    {
        return in_array($this, [self::Grading, self::Published, self::Finalized, self::Archived], true);
    }

    public function isReadOnly(): bool
    {
        return $this === self::Archived;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'ฉบับร่าง',
            self::Active => 'กำลังเปิดเรียน',
            self::EnrollmentClosed => 'ปิดรับสมัคร',
            self::Grading => 'กำลังออกเกรด',
            self::Published => 'ประกาศเกรดแล้ว',
            self::Finalized => 'สิ้นสุดการเรียน',
            self::Archived => 'เก็บถาวร',
        };
    }
}
