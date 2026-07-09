<?php

namespace App\Http\Controllers\Api\Learn\Student\Card\Concerns;

use App\Models\Classroom;

trait ResolvesStudentCardRoom
{
    private function resolveClassroomFromUrl(string $level, string $room): Classroom
    {
        $classrooms = Classroom::query()
            ->where('grade_level', 'like', '%'.$level)
            ->where('section', $room)
            ->where('status', 'active')
            ->whereHas('academicYear', fn ($query) => $query->where('is_current', true))
            ->with(['academy', 'academicYear', 'homeroomTeacher:id,name'])
            ->get();

        abort_if($classrooms->isEmpty(), 404, 'ไม่พบห้องเรียนในปีการศึกษาปัจจุบัน');
        abort_if(
            $classrooms->count() > 1,
            409,
            'พบห้องเรียนตรงกันมากกว่าหนึ่งโรงเรียน ไม่สามารถระบุห้องได้'
        );

        return $classrooms->first();
    }
}
