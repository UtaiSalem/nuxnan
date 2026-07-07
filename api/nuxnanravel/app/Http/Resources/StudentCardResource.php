<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $student = $this->student;

        $enrollment = $student?->classroomEnrollments
            ?->first(fn ($item) => $item->status === 'active' && $item->classroom?->academicYear?->is_current);

        $birthDateString = null;
        if ($student && $student->date_of_birth) {
            $birthDateString = Carbon::parse($student->date_of_birth)->format('d/m/Y');
        }

        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'academy_id' => $this->academy_id,
            'student_number' => $student ? $student->student_id : $this->student_number,
            'national_id' => $student ? $student->citizen_id : $this->national_id,
            'title_name' => $student ? $student->title_prefix_th : $this->title_name,
            'first_name_thai' => $student ? $student->first_name_th : $this->first_name_thai,
            'last_name_thai' => $student ? $student->last_name_th : $this->last_name_thai,
            'full_name_thai' => $student ? trim("{$student->title_prefix_th} {$student->first_name_th} {$student->last_name_th}") : $this->full_name_thai,
            'first_name_english' => $student ? $student->first_name_en : $this->first_name_english,
            'birth_date' => $student ? $student->date_of_birth : $this->birth_date,
            'birth_date_string' => $birthDateString ?: $this->birth_date_string,
            'class_level' => $enrollment ? $this->numericGradeLevel($enrollment->classroom->grade_level) : $this->class_level,
            'class_section' => $enrollment ? (int) $enrollment->classroom->section : $this->class_section,
            'level_and_room' => $enrollment ? $this->numericGradeLevel($enrollment->classroom->grade_level).'/'.$enrollment->classroom->section : $this->level_and_room,
            'card_issue_date' => $this->card_issue_date,
            'card_expiry_date' => $this->card_expiry_date,
            'student_status' => $this->student_status,
            'profile_image' => $student ? $student->profile_image : $this->profile_image,
            'profile_image_url' => $student ? $student->profile_image_url : $this->profile_image_url,
            'qr_content' => $this->qr_content,
            'qr_url' => $this->qr_url,
            'order_no' => $enrollment ? $enrollment->student_number : $this->order_no,
        ];
    }

    private function numericGradeLevel(?string $grade): int
    {
        if (! $grade) {
            return 0;
        }
        if (! preg_match('/(\d+)\s*$/u', trim($grade), $matches)) {
            return 0;
        }

        return (int) $matches[1];
    }
}
