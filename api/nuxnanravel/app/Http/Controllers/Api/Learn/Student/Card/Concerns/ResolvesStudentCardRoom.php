<?php

namespace App\Http\Controllers\Api\Learn\Student\Card\Concerns;

use App\Models\Academy;
use App\Models\Classroom;

trait ResolvesStudentCardRoom
{
    protected static array $studentCardGradeLevelValues = [];

    private function resolveClassroomFromUrl(string $level, string $room): Classroom
    {
        $candidateClassrooms = Classroom::query()
            ->where('section', $room)
            ->where('status', 'active')
            ->whereHas('academicYear', fn ($query) => $query->where('is_current', true))
            ->with(['academy', 'academicYear', 'homeroomTeacher:id,name'])
            ->get();
        $gradeLevelValues = $candidateClassrooms->pluck('grade_level')->unique()
            ->filter(fn ($grade) => (int) preg_replace('/\D+/', '', (string) $grade) === (int) preg_replace('/\D+/', '', $level))
            ->values()->all();
        static::$studentCardGradeLevelValues[$level] = $gradeLevelValues;
        request()->attributes->set('student_card_grade_level_values_'.$level, $gradeLevelValues);
        $classrooms = $candidateClassrooms->whereIn('grade_level', $gradeLevelValues)->values();

        $academy = request()->route('academy');
        if ($academy !== null) {
            $academyId = $academy instanceof Academy ? $academy->id : $academy;
            $classrooms = $classrooms->where('academy_id', $academyId)->values();
        }

        abort_if($classrooms->isEmpty(), 404, 'ไม่พบห้องเรียนในปีการศึกษาปัจจุบัน');
        abort_if(
            $classrooms->count() > 1,
            409,
            'พบห้องเรียนตรงกันมากกว่าหนึ่งโรงเรียน ไม่สามารถระบุห้องได้'
        );

        return $classrooms->first();
    }
}
