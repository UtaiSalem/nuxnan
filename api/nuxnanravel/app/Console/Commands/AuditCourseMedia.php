<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Course;
use App\Models\LessonImage;
use App\Models\TopicImage;
use App\Models\AssignmentImage;
use App\Models\QuestionImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class AuditCourseMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courses:audit-media {--fix : Attempt to fix some issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit course media files for duplicates, missing files, and orphaned files.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Course Media Audit...');

        $this->auditDuplicates();
        $this->auditMissingFiles();
        $this->auditOrphanedFiles();

        $this->info('Audit completed.');
    }

    protected function auditDuplicates()
    {
        $this->info('Checking for duplicate media usage across records...');

        $mediaTypes = [
            'Course Cover' => [Course::class, 'cover'],
            'Course Logo' => [Course::class, 'logo'],
            'Lesson Image' => [LessonImage::class, 'filename'],
            'Topic Image' => [TopicImage::class, 'filename'],
            'Assignment Image' => [AssignmentImage::class, 'image_url'],
            'Question/Option Image' => [QuestionImage::class, 'filename'],
        ];

        foreach ($mediaTypes as $label => $config) {
            [$model, $field] = $config;
            $duplicates = $model::select($field)
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->groupBy($field)
                ->havingRaw('COUNT(*) > 1')
                ->pluck($field);

            if ($duplicates->isNotEmpty()) {
                $this->warn("{$label}: Found " . $duplicates->count() . " filenames used by multiple records.");
                foreach ($duplicates as $filename) {
                    $count = $model::where($field, $filename)->count();
                    $this->line("  - {$filename} (used by {$count} records)");
                }
            } else {
                $this->info("{$label}: No duplicates found.");
            }
        }
    }

    protected function auditMissingFiles()
    {
        $this->info('Checking for database records with missing physical files...');

        $configs = [
            'Course Cover' => [Course::class, 'cover', 'images/courses/covers'],
            'Course Logo' => [Course::class, 'logo', 'images/courses/logos'],
            'Lesson Image' => [LessonImage::class, 'filename', 'images/courses/lessons'],
            'Topic Image' => [TopicImage::class, 'filename', 'images/courses/lessons/topics'],
            'Assignment Image' => [AssignmentImage::class, 'image_url', 'images/courses/assignments'],
        ];

        foreach ($configs as $label => $config) {
            [$model, $field, $path] = $config;
            $records = $model::whereNotNull($field)->where($field, '!=', '')->get();
            $missingCount = 0;

            foreach ($records as $record) {
                if (!Storage::disk('public')->exists($path . '/' . $record->$field)) {
                    $missingCount++;
                    $this->error("Missing file for {$label} ID {$record->id}: {$path}/{$record->$field}");
                }
            }

            if ($missingCount === 0) {
                $this->info("{$label}: All physical files exist.");
            }
        }
    }

    protected function auditOrphanedFiles()
    {
        $this->info('Checking for orphaned files in storage (no DB record references)...');

        $paths = [
            'images/courses/covers' => [Course::class, 'cover'],
            'images/courses/logos' => [Course::class, 'logo'],
            'images/courses/lessons' => [LessonImage::class, 'filename'],
            'images/courses/lessons/topics' => [TopicImage::class, 'filename'],
            'images/courses/assignments' => [AssignmentImage::class, 'image_url'],
        ];

        foreach ($paths as $path => $config) {
            [$model, $field] = $config;
            
            if (!Storage::disk('public')->exists($path)) {
                continue;
            }

            $files = Storage::disk('public')->files($path);
            $orphanedCount = 0;

            foreach ($files as $filePath) {
                $filename = basename($filePath);
                $existsInDb = $model::where($field, $filename)->exists();

                if (!$existsInDb) {
                    $orphanedCount++;
                    $this->warn("Orphaned file: {$filePath}");
                }
            }

            if ($orphanedCount === 0) {
                $this->info("Path {$path}: No orphaned files found.");
            }
        }
    }
}
