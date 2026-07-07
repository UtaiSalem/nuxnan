<?php

namespace App\Observers;

use App\Models\ClassroomStudent;
use App\Models\StudentCard;

class ClassroomStudentObserver
{
    /**
     * Handle the ClassroomStudent "created" event.
     *
     * Re-enrolling a student whose card was expired by a previous
     * close (transfer/remove) must bring the card back, otherwise the
     * student disappears from every room endpoint.
     */
    public function created(ClassroomStudent $classroomStudent): void
    {
        if ($classroomStudent->status === ClassroomStudent::STATUS_ACTIVE) {
            $this->reactivateCard($classroomStudent->student_id);
        }
    }

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
            } elseif ($status === ClassroomStudent::STATUS_ACTIVE) {
                $this->reactivateCard($classroomStudent->student_id);
            } else {
                StudentCard::where('student_id', $classroomStudent->student_id)
                    ->where('student_status', 'active')
                    ->update(['student_status' => 'expired']);
            }
        }
    }

    private function reactivateCard(int $studentId): void
    {
        StudentCard::where('student_id', $studentId)
            ->where('student_status', 'expired')
            ->update(['student_status' => 'active']);
    }
}
