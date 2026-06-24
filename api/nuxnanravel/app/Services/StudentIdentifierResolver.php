<?php

namespace App\Services;

use App\Models\AcademyMember;
use App\Models\Student;
use App\Models\StudentCard;

/**
 * Resolves a scanned/typed identifier (member_code or student card number)
 * to a school member's user account. Shared by any "scan card or enter ID"
 * check-in flow (school roll call, activity attendance, etc).
 */
class StudentIdentifierResolver
{
    /**
     * @return array{user_id: ?int, student_name: ?string, student_photo: ?string}
     */
    public function resolve(int $academyId, string $identifier): array
    {
        $identifier = trim($identifier);
        $userId = null;
        $studentName = null;
        $studentPhoto = null;

        // --- Strategy 1: member_code (numeric) ---
        if (is_numeric($identifier)) {
            $member = AcademyMember::where('academy_id', $academyId)
                ->where('member_code', (int) $identifier)
                ->with('user:id,name,profile_photo_path')
                ->first();

            if ($member && $member->user_id) {
                $userId = $member->user_id;
                $studentName = $member->user?->name;
                $studentPhoto = $member->user?->profile_photo_path;
            }
        }

        // --- Strategy 2: student_number from student_cards ---
        if (! $userId) {
            $card = StudentCard::where('academy_id', $academyId)
                ->where('student_number', $identifier)
                ->first();

            if ($card) {
                $student = Student::where('student_id', $card->student_number)
                    ->orWhere('citizen_id', $card->national_id)
                    ->first();

                if ($student) {
                    $member = AcademyMember::where('academy_id', $academyId)
                        ->where('student_id', $student->id)
                        ->with('user:id,name,profile_photo_path')
                        ->first();

                    if ($member && $member->user_id) {
                        $userId = $member->user_id;
                        $studentName = $member->user?->name ?? $card->full_name_thai;
                        $studentPhoto = $member->user?->profile_photo_path;
                    }
                }

                // Fallback: name from card even without user link
                if (! $userId) {
                    $studentName = $card->full_name_thai;
                }
            }
        }

        return [
            'user_id' => $userId,
            'student_name' => $studentName,
            'student_photo' => $studentPhoto,
        ];
    }
}
