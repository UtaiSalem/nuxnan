<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentCard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentPhotoService
{
    /**
     * Get the canonical relative path for a student's profile photo.
     */
    public function canonicalPath(Student $student, ?string $extension = 'jpg'): string
    {
        $ext = $extension ?: 'jpg';

        return "images/students/profiles/{$student->id}.{$ext}";
    }

    /**
     * Get the full URL of the student's profile photo.
     */
    public function url(Student $student): ?string
    {
        if (! $student->profile_image) {
            return null;
        }

        if (str_starts_with($student->profile_image, 'images/')) {
            return asset('storage/'.$student->profile_image);
        }

        // Fallback for legacy format: filename only
        if ($student->class_level && $student->class_section) {
            $level = (int) str_replace('ม.', '', $student->class_level);
            $section = (int) $student->class_section;

            return asset("storage/images/students/{$level}/{$section}/{$student->profile_image}");
        }

        return null;
    }

    /**
     * Store/update a student's profile photo in the canonical location.
     */
    public function store(Student $student, UploadedFile $photo): string
    {
        $extension = $photo->getClientOriginalExtension();
        $canonicalRelativePath = $this->canonicalPath($student, $extension);

        // Put the file directly using Storage disk public
        $dir = dirname($canonicalRelativePath);
        $filename = basename($canonicalRelativePath);

        Storage::disk('public')->putFileAs($dir, $photo, $filename);

        // Update student record
        $student->update([
            'profile_image' => $canonicalRelativePath,
        ]);

        // Also sync to student cards if active card exists
        StudentCard::where('student_id', $student->id)
            ->where('academy_id', $student->academy_id)
            ->update([
                'profile_image' => $canonicalRelativePath,
            ]);

        return $canonicalRelativePath;
    }

    /**
     * Delete student's profile photo.
     */
    public function delete(Student $student): bool
    {
        if (! $student->profile_image) {
            return false;
        }

        $pathsToDelete = [];

        // Canonical or relative path
        if (str_starts_with($student->profile_image, 'images/')) {
            $pathsToDelete[] = $student->profile_image;
        } else {
            // Legacy path delete
            if ($student->class_level && $student->class_section) {
                $level = (int) str_replace('ม.', '', $student->class_level);
                $section = (int) $student->class_section;
                $pathsToDelete[] = "images/students/{$level}/{$section}/{$student->profile_image}";
            }
        }

        foreach ($pathsToDelete as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        // Reset DB fields
        $student->update(['profile_image' => null]);

        StudentCard::where('student_id', $student->id)
            ->where('academy_id', $student->academy_id)
            ->update(['profile_image' => null]);

        return true;
    }

    /**
     * Check if a student's photo exists in the canonical location.
     */
    public function exists(Student $student): bool
    {
        if (! $student->profile_image) {
            return false;
        }

        if (str_starts_with($student->profile_image, 'images/')) {
            return Storage::disk('public')->exists($student->profile_image);
        }

        return false;
    }

    /**
     * Resolve legacy old path from level and section.
     */
    public function resolveOldPath(Student $student, ?string $level, ?string $section): ?string
    {
        if (! $student->profile_image) {
            return null;
        }

        // Extract raw filename if it is already a full path
        $filename = basename($student->profile_image);

        if (! $level || ! $section) {
            return null;
        }

        $levelNum = (int) str_replace('ม.', '', $level);
        $sectionNum = (int) $section;

        return "images/students/{$levelNum}/{$sectionNum}/{$filename}";
    }

    /**
     * Find existing photo in various legacy and current paths.
     */
    public function findPhotoInLegacyPaths(Student $student): ?string
    {
        if (! $student->profile_image) {
            return null;
        }

        $filename = basename($student->profile_image);

        // 1. Check if already in canonical (profiles/) path
        // For new path format
        if (str_starts_with($student->profile_image, 'images/')) {
            if (Storage::disk('public')->exists($student->profile_image)) {
                return $student->profile_image;
            }
        } else {
            $profilesPath = "images/students/profiles/{$student->id}.".pathinfo($filename, PATHINFO_EXTENSION);
            if (Storage::disk('public')->exists($profilesPath)) {
                return $profilesPath;
            }
        }

        // 2. Check path from active StudentCard current class_level/class_section
        $card = StudentCard::where('student_id', $student->id)->where('student_status', 'active')->first();
        if ($card && $card->class_level && $card->class_section) {
            $levelNum = (int) str_replace('ม.', '', $card->class_level);
            $sectionNum = (int) $card->class_section;
            $path = "images/students/{$levelNum}/{$sectionNum}/{$filename}";
            if (Storage::disk('public')->exists($path)) {
                return $path;
            }
        }

        // 3. Check path from Student's own current class_level/class_section
        if ($student->class_level && $student->class_section) {
            $levelNum = (int) str_replace('ม.', '', $student->class_level);
            $sectionNum = (int) $student->class_section;
            $path = "images/students/{$levelNum}/{$sectionNum}/{$filename}";
            if (Storage::disk('public')->exists($path)) {
                return $path;
            }
        }

        // 4. Try from Rollover Batch if there is a committed rollover batch summary
        $latestBatch = DB::table('rollover_batches')
            ->where('academy_id', $student->academy_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestBatch && $latestBatch->plan_summary) {
            $summary = json_decode($latestBatch->plan_summary, true);
            if (isset($summary['before'][$student->id])) {
                $before = $summary['before'][$student->id];
                $oldLevel = $before['class_level'] ?? null;
                $oldSection = $before['class_section'] ?? null;
                if ($oldLevel && $oldSection) {
                    $levelNum = (int) str_replace('ม.', '', $oldLevel);
                    $sectionNum = (int) $oldSection;
                    $path = "images/students/{$levelNum}/{$sectionNum}/{$filename}";
                    if (Storage::disk('public')->exists($path)) {
                        return $path;
                    }
                }
            }
        }

        // 5. Try using classroom_students that are promoted or graduated
        $promotedClassrooms = DB::table('classroom_students')
            ->join('classrooms', 'classroom_students.classroom_id', '=', 'classrooms.id')
            ->where('classroom_students.student_id', $student->id)
            ->whereIn('classroom_students.status', ['promoted', 'graduated'])
            ->select('classrooms.grade_level', 'classrooms.section')
            ->get();

        foreach ($promotedClassrooms as $class) {
            $levelNum = (int) str_replace('ม.', '', $class->grade_level);
            $sectionNum = (int) $class->section;
            $path = "images/students/{$levelNum}/{$sectionNum}/{$filename}";
            if (Storage::disk('public')->exists($path)) {
                return $path;
            }
        }

        // 6. Try using student_academic_info history records
        $historyInfos = DB::table('student_academic_info')
            ->where('student_id', $student->id)
            ->where('is_current', 0)
            ->select('current_grade', 'current_class')
            ->get();

        foreach ($historyInfos as $info) {
            if ($info->current_grade && $info->current_class) {
                $levelNum = (int) str_replace('ม.', '', $info->current_grade);
                $sectionNum = (int) $info->current_class;
                $path = "images/students/{$levelNum}/{$sectionNum}/{$filename}";
                if (Storage::disk('public')->exists($path)) {
                    return $path;
                }
            }
        }

        // 7. Recursive fallback: find file anywhere inside images/students/
        $allFiles = Storage::disk('public')->allFiles('images/students');
        foreach ($allFiles as $filePath) {
            if (basename($filePath) === $filename) {
                return $filePath;
            }
        }

        return null;
    }
}
