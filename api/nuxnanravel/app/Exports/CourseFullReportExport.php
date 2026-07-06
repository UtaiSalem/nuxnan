<?php

namespace App\Exports;

use App\Models\Course;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CourseFullReportExport implements WithMultipleSheets
{
    protected Course $course;

    protected array $gradesData;

    protected array $attendanceData;

    protected array $progressData;

    protected array $stats;

    public function __construct(
        Course $course,
        array $gradesData,
        array $attendanceData,
        array $progressData,
        array $stats
    ) {
        $this->course = $course;
        $this->gradesData = $gradesData;
        $this->attendanceData = $attendanceData;
        $this->progressData = $progressData;
        $this->stats = $stats;
    }

    public function sheets(): array
    {
        return [
            'สรุป' => new CourseStatsSummarySheet($this->course, $this->stats),
            'ผลการเรียน' => new CourseGradesExport($this->gradesData, $this->course),
            'การเข้าเรียน' => new CourseAttendanceExport($this->attendanceData, $this->course, collect()),
            'ความคืบหน้า' => new CourseProgressExport($this->progressData, $this->course),
        ];
    }
}
