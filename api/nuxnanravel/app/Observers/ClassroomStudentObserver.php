<?php

namespace App\Observers;

use App\Models\ClassroomStudent;
use App\Models\StudentCard;

class ClassroomStudentObserver
{
    /**
     * Handle the ClassroomStudent "updated" event.
     */
    public function updated(ClassroomStudent $classroomStudent): void
    {
        if ($classroomStudent->isDirty('status')) {
            $status = $classroomStudent->status;

            if ($status === 'graduated') {
                StudentCard::where('student_id', $classroomStudent->student_id)
                    ->where('student_status', 'active')
                    ->update(['student_status' => 'graduated']);
            } elseif ($status !== 'active') {
                StudentCard::where('student_id', $classroomStudent->student_id)
                    ->where('student_status', 'active')
                    ->update(['student_status' => 'expired']);
            }
        }
    }
}
