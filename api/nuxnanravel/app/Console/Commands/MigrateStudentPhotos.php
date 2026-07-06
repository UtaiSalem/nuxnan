<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\StudentCard;
use App\Services\StudentPhotoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrateStudentPhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:migrate-photos {--academy=1} {--dry-run} {--commit} {--report=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy student profile photos to canonical path and update database records';

    /**
     * Execute the console command.
     */
    public function handle(StudentPhotoService $photoService)
    {
        $academyId = $this->option('academy');
        $isDryRun = $this->option('dry-run');
        $isCommit = $this->option('commit');
        $reportPath = $this->option('report');

        if (! $isDryRun && ! $isCommit) {
            $this->error('You must specify either --dry-run or --commit');

            return 1;
        }

        $students = Student::where('academy_id', $academyId)
            ->whereNotNull('profile_image')
            ->where('profile_image', '!=', '')
            ->get();

        $this->info('Found '.$students->count().' students with profile images to audit/migrate.');

        $results = [];
        $counts = [
            'already_migrated' => 0,
            'found_single' => 0,
            'found_multiple' => 0,
            'not_found' => 0,
            'copied' => 0,
            'db_updated' => 0,
            'skipped_identical' => 0,
        ];

        $bar = $this->output->createProgressBar($students->count());
        $bar->start();

        foreach ($students as $student) {
            $status = 'unknown';
            $oldPath = null;
            $newPath = null;
            $action = 'none';
            $notes = '';

            // Check if already in canonical path
            if (str_starts_with($student->profile_image, 'images/students/profiles/')) {
                if (Storage::disk('public')->exists($student->profile_image)) {
                    $status = 'already_migrated';
                    $counts['already_migrated']++;
                    $newPath = $student->profile_image;
                } else {
                    $status = 'not_found';
                    $counts['not_found']++;
                    $notes = 'DB points to canonical path but file does not exist in storage';
                }
            } else {
                // Find file in legacy paths
                $legacyPath = $photoService->findPhotoInLegacyPaths($student);

                if ($legacyPath) {
                    $status = 'found_single';
                    $counts['found_single']++;
                    $oldPath = $legacyPath;

                    $ext = pathinfo($legacyPath, PATHINFO_EXTENSION);
                    $newPath = $photoService->canonicalPath($student, $ext);

                    if ($isCommit) {
                        try {
                            $oldExists = Storage::disk('public')->exists($oldPath);
                            $newExists = Storage::disk('public')->exists($newPath);

                            $shouldCopy = true;
                            if ($newExists) {
                                // Compare checksums
                                $oldMd5 = md5(Storage::disk('public')->get($oldPath));
                                $newMd5 = md5(Storage::disk('public')->get($newPath));
                                if ($oldMd5 === $newMd5) {
                                    $shouldCopy = false;
                                    $counts['skipped_identical']++;
                                    $action = 'skipped_identical';
                                }
                            }

                            if ($shouldCopy) {
                                // Ensure destination directory exists
                                $destDir = dirname($newPath);
                                if (! Storage::disk('public')->exists($destDir)) {
                                    Storage::disk('public')->makeDirectory($destDir);
                                }

                                Storage::disk('public')->copy($oldPath, $newPath);
                                $counts['copied']++;
                                $action = 'copied';
                            }

                            // Update DB
                            $student->update(['profile_image' => $newPath]);

                            // Also update matching student cards
                            StudentCard::where('student_id', $student->id)
                                ->where('academy_id', $student->academy_id)
                                ->update(['profile_image' => $newPath]);

                            $counts['db_updated']++;
                            if ($action === 'none') {
                                $action = 'db_updated_only';
                            } else {
                                $action .= '_and_db_updated';
                            }
                        } catch (\Exception $e) {
                            $action = 'error';
                            $notes = 'Error during commit: '.$e->getMessage();
                        }
                    }
                } else {
                    $status = 'not_found';
                    $counts['not_found']++;
                    $notes = 'File not found in any legacy path';
                }
            }

            $results[] = [
                'student_id' => $student->id,
                'student_number' => $student->student_id,
                'name' => "{$student->title_prefix_th}{$student->first_name_th} {$student->last_name_th}",
                'status' => $status,
                'old_path' => $oldPath,
                'new_path' => $newPath,
                'action' => $action,
                'notes' => $notes,
            ];

            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        // Print Summary table
        $this->info('Migration Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Already Migrated (Correct)', $counts['already_migrated']],
                ['Found in Legacy Paths', $counts['found_single']],
                ['Not Found (Missing Photos)', $counts['not_found']],
                ['Copied Files', $counts['copied']],
                ['Skipped Identical Files', $counts['skipped_identical']],
                ['Database Records Updated', $counts['db_updated']],
            ]
        );

        // Export Report if requested
        if ($reportPath) {
            $dir = dirname($reportPath);
            if ($dir && $dir !== '.' && ! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $fp = fopen($reportPath, 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($fp, ['student_id', 'student_number', 'name', 'status', 'old_path', 'new_path', 'action', 'notes']);
            foreach ($results as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            $this->info("Report exported to: {$reportPath}");
        }

        return 0;
    }
}
