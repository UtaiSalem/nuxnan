<?php

namespace App\Console\Commands;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateCourseAcademicInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'course:update-academic-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update semester and academic_year for existing courses based on created_at';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $courses = Course::all();
        $this->info("Found {$courses->count()} courses to update.");

        foreach ($courses as $course) {
            $createdAt = Carbon::parse($course->created_at);
            $month = $createdAt->month;
            $year = $createdAt->year;
            $thaiYear = $year + 543;

            $semester = '';
            $academicYear = '';

            // Logic:
            // May (5) to Sep (9): Semester 1, Year = Current
            // Oct (10) to Mar (3): Semester 2
            //    - Oct-Dec: Year = Current
            //    - Jan-Mar: Year = Current - 1 (Academic Year started prev year)
            // Apr (4): Summer, Year = Current - 1

            if ($month >= 5 && $month <= 9) {
                // Semester 1
                $semester = '1';
                $academicYear = $thaiYear;
            } elseif ($month >= 10 && $month <= 12) {
                // Semester 2, Same Year
                $semester = '2';
                $academicYear = $thaiYear;
            } elseif ($month >= 1 && $month <= 3) {
                // Semester 2, Previous Year
                // Note: user said "Oct-Mar is Semester 2".
                // If created in Jan 2026 (2569), it belongs to Academic Year 2568.
                $semester = '2';
                $academicYear = $thaiYear - 1;
            } else {
                // Month 4 (April) - Summer
                $semester = 'summer';
                $academicYear = $thaiYear - 1;
            }

            $course->semester = $semester;
            $course->academic_year = (string) $academicYear;
            $course->save();

            $this->line("Updated Course ID {$course->id}: {$createdAt->toDateString()} -> Sem {$semester} / {$academicYear}");
        }

        $this->info('All courses updated successfully.');
    }
}
