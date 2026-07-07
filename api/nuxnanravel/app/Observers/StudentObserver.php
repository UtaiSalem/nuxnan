<?php

namespace App\Observers;

use App\Models\Student;
use App\Models\StudentCard;
use Carbon\Carbon;

class StudentObserver
{
    /**
     * Handle the Student "updated" event.
     */
    public function updated(Student $student): void
    {
        // Find active cards for this student
        $cards = StudentCard::where('student_id', $student->id)
            ->where('student_status', 'active')
            ->get();

        foreach ($cards as $card) {
            $title = $student->title_prefix_th ?? '';
            $first = $student->first_name_th ?? '';
            $last = $student->last_name_th ?? '';
            $fullName = trim("{$title} {$first} {$last}");

            $birthDateString = null;
            if ($student->date_of_birth) {
                $birthDateString = Carbon::parse($student->date_of_birth)->format('d/m/Y');
            }

            $card->update([
                'student_number' => $student->student_id,
                'national_id' => $student->citizen_id,
                'title_name' => $title,
                'first_name_thai' => $first,
                'last_name_thai' => $last,
                'full_name_thai' => $fullName,
                'first_name_english' => $student->first_name_en,
                'birth_date' => $student->date_of_birth,
                'birth_date_string' => $birthDateString,
                'profile_image' => $student->profile_image,
            ]);
        }
    }
}
