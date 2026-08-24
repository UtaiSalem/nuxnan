<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\MemberActivityLog;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\User;

/**
 * Writes guardian changes to member_activity_logs — the same table the academy
 * activity-log screen reads. Logging must never break the request it describes,
 * so every failure is reported and swallowed.
 *
 * Sensitive values are never copied into the log: an audit trail that stores the
 * citizen id it is auditing would hand the field to anyone who can read the log.
 */
class GuardianAuditLogger
{
    /** Dedupe window: the same viewer opening the same student again is not a new event. */
    private const VIEW_DEDUPE_MINUTES = 60;

    /**
     * Record that someone other than the student read a guardian's citizen id or income.
     *
     * Call it only when the response really carried those fields: a log row means the data
     * left the server, so a row that does not mean that makes the whole table untrustworthy.
     */
    public function sensitiveViewed(?User $user, Student $student): void
    {
        if ($user === null) {
            return;
        }

        // The student reading their own family's data is not an access event.
        if ($student->user_id !== null && $student->user_id === $user->id) {
            return;
        }

        try {
            $alreadyLogged = MemberActivityLog::query()
                ->where('academy_id', $student->academy_id)
                ->where('user_id', $user->id)
                ->where('action', MemberActivityLog::ACTION_GUARDIAN_SENSITIVE_VIEW)
                ->where('new_values->student_id', $student->id)
                ->where('created_at', '>=', now()->subMinutes(self::VIEW_DEDUPE_MINUTES))
                ->exists();

            if ($alreadyLogged) {
                return;
            }

            $this->write(
                $student,
                MemberActivityLog::ACTION_GUARDIAN_SENSITIVE_VIEW,
                'เปิดดูข้อมูลอ่อนไหวของผู้ปกครอง นักเรียน: '.$this->studentName($student),
                null,
                ['student_id' => $student->id]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function created(Student $student, StudentGuardian $guardian, array $input): void
    {
        $this->write($student, MemberActivityLog::ACTION_GUARDIAN_CREATE,
            'เพิ่มผู้ปกครอง: '.$this->name($guardian).' ของนักเรียน '.$this->studentName($student),
            null,
            $this->redact(['guardian_id' => $guardian->id] + $this->trackedFields($input))
        );
    }

    public function updated(Student $student, StudentGuardian $guardian, array $changes): void
    {
        $this->write($student, MemberActivityLog::ACTION_GUARDIAN_UPDATE,
            'แก้ไขข้อมูลผู้ปกครอง: '.$this->name($guardian).' ของนักเรียน '.$this->studentName($student),
            null,
            $this->redact(['guardian_id' => $guardian->id] + $this->trackedFields($changes))
        );
    }

    public function deleted(Student $student, StudentGuardian $guardian): void
    {
        $this->write($student, MemberActivityLog::ACTION_GUARDIAN_DELETE,
            'ลบผู้ปกครอง: '.$this->name($guardian).' ของนักเรียน '.$this->studentName($student),
            ['guardian_id' => $guardian->id, 'full_name' => $this->name($guardian)],
            null
        );
    }

    public function appointed(Student $student, Guardian $person, string $actorRole, array $linkData): void
    {
        $this->write($student, MemberActivityLog::ACTION_GUARDIAN_APPOINT,
            'แต่งตั้งผู้ปกครอง: '.$this->personName($person).' ให้นักเรียน '.$this->studentName($student),
            null,
            ['guardian_person_id' => $person->id, 'appointed_by_role' => $actorRole]
                + $this->trackedFields($linkData)
        );
    }

    public function verified(Student $student, Guardian $person): void
    {
        $this->write($student, MemberActivityLog::ACTION_GUARDIAN_VERIFY,
            'ยืนยันการแต่งตั้งผู้ปกครอง: '.$this->personName($person).' ของนักเรียน '.$this->studentName($student),
            null,
            ['guardian_person_id' => $person->id]
        );
    }

    private function personName(Guardian $person): string
    {
        return trim(($person->title_prefix ? $person->title_prefix.' ' : '').$person->first_name.' '.$person->last_name);
    }

    /** Only the fields the API actually accepts, so stray keys never reach the log. */
    private function trackedFields(array $input): array
    {
        $allowedKeys = [
            'guardian_type', 'title_prefix', 'first_name', 'last_name', 'relationship', 'occupation',
            'workplace', 'nationality', 'is_primary_contact', 'is_emergency_contact', 'status',
            'citizen_id', 'monthly_income',
        ];

        return array_intersect_key($input, array_flip($allowedKeys));
    }

    /** Sensitive fields are recorded as "changed", never as their value. */
    private function redact(array $values): array
    {
        foreach (GuardianAccessService::SENSITIVE_FIELDS as $field) {
            if (array_key_exists($field, $values)) {
                $values[$field] = 'changed';
            }
        }

        return $values;
    }

    private function name(StudentGuardian $guardian): string
    {
        return trim(($guardian->title_prefix ? $guardian->title_prefix.' ' : '').$guardian->first_name.' '.$guardian->last_name);
    }

    /** Students carry Thai-suffixed name columns, so full_name_th is the only accessor that fills in. */
    private function studentName(Student $student): string
    {
        $name = trim((string) $student->full_name_th);

        return $name !== '' ? $name : (string) $student->student_id;
    }

    private function write(Student $student, string $action, string $description, ?array $old, ?array $new): void
    {
        try {
            MemberActivityLog::logActivity([
                'academy_id' => $student->academy_id,
                'target_user_id' => $student->user_id,
                'action' => $action,
                'action_category' => MemberActivityLog::CATEGORY_MEMBER,
                'description' => $description,
                'old_values' => $old,
                'new_values' => $new,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
