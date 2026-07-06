<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\StudentPhotoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanupLegacyStudentPhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:cleanup-legacy-photos {--academy=1} {--dry-run} {--commit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up legacy student photo files after verifying successful migration';

    /**
     * Execute the console command.
     */
    public function handle(StudentPhotoService $photoService)
    {
        $academyId = $this->option('academy');
        $isDryRun = $this->option('dry-run');
        $isCommit = $this->option('commit');

        if (! $isDryRun && ! $isCommit) {
            $this->error('You must specify either --dry-run or --commit');

            return 1;
        }

        if ($isCommit && ! $this->confirm('WARNING: This will permanently delete legacy student photo files. Are you sure you have verified the migration and want to proceed?')) {
            $this->info('Cleanup cancelled.');

            return 0;
        }

        // We only clean up for students whose profile_image is set to a canonical path
        $students = Student::where('academy_id', $academyId)
            ->whereNotNull('profile_image')
            ->where('profile_image', 'LIKE', 'images/students/profiles/%')
            ->get();

        $this->info('Scanning '.$students->count().' migrated students for legacy photo cleanup...');

        $filesToDelete = [];
        $totalBytesReclaimed = 0;
        $scanCounts = [
            'legacy_files_found' => 0,
            'checksum_mismatch' => 0,
            'deleted' => 0,
        ];

        foreach ($students as $student) {
            $canonicalPath = $student->profile_image;
            if (! Storage::disk('public')->exists($canonicalPath)) {
                $this->warn("Canonical photo does not exist for Student ID {$student->id}: {$canonicalPath}. Skipping legacy checks for safety.");

                continue;
            }

            $canonicalMd5 = md5(Storage::disk('public')->get($canonicalPath));
            $filename = basename($canonicalPath);

            // We look for legacy files in the images/students/{level}/{section}/ hierarchy
            // 1. Current student level/section
            $legacyPaths = [];
            if ($student->class_level && $student->class_section) {
                $levelNum = (int) str_replace('ม.', '', $student->class_level);
                $sectionNum = (int) $student->class_section;
                $legacyPaths[] = "images/students/{$levelNum}/{$sectionNum}/{$filename}";
            }

            // 2. ClassroomStudent history
            $classroomHistory = DB::table('classroom_students')
                ->join('classrooms', 'classroom_students.classroom_id', '=', 'classrooms.id')
                ->where('classroom_students.student_id', $student->id)
                ->select('classrooms.grade_level', 'classrooms.section')
                ->get();

            foreach ($classroomHistory as $class) {
                $levelNum = (int) str_replace('ม.', '', $class->grade_level);
                $sectionNum = (int) $class->section;
                $legacyPaths[] = "images/students/{$levelNum}/{$sectionNum}/{$filename}";
            }

            // De-duplicate paths
            $legacyPaths = array_unique($legacyPaths);

            foreach ($legacyPaths as $legacyPath) {
                // Ensure we don't delete the canonical file itself!
                if ($legacyPath === $canonicalPath || str_contains($legacyPath, 'images/students/profiles/')) {
                    continue;
                }

                if (Storage::disk('public')->exists($legacyPath)) {
                    // Compare checksum for safety
                    $legacyMd5 = md5(Storage::disk('public')->get($legacyPath));
                    if ($canonicalMd5 === $legacyMd5) {
                        $scanCounts['legacy_files_found']++;
                        $size = Storage::disk('public')->size($legacyPath);
                        $totalBytesReclaimed += $size;
                        $filesToDelete[] = $legacyPath;
                    } else {
                        $scanCounts['checksum_mismatch']++;
                        $this->warn("Checksum mismatch for Student ID {$student->id} between legacy ({$legacyPath}) and canonical ({$canonicalPath}). NOT deleting legacy file.");
                    }
                }
            }
        }

        $this->info('Legacy files found matching migrated photos: '.$scanCounts['legacy_files_found']);
        $this->info('Checksum mismatches (skipped): '.$scanCounts['checksum_mismatch']);
        $this->info('Total size to reclaim: '.round($totalBytesReclaimed / 1024 / 1024, 2).' MB');

        if ($isCommit && count($filesToDelete) > 0) {
            $this->info('Starting deletion of legacy files...');
            $bar = $this->output->createProgressBar(count($filesToDelete));
            $bar->start();
            foreach ($filesToDelete as $file) {
                Storage::disk('public')->delete($file);
                $scanCounts['deleted']++;
                $bar->advance();
            }
            $bar->finish();
            $this->line('');
            $this->info("Successfully deleted {$scanCounts['deleted']} legacy photo files.");
        } else {
            $this->info('Dry run completed. No files were deleted.');
        }

        return 0;
    }
}
