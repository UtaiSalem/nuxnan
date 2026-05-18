<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CourseMediaService
{
    /**
     * Paths for various course-related media.
     */
    const PATHS = [
        'course_cover'     => 'images/courses/covers',
        'course_logo'      => 'images/courses/logos',
        'lesson_image'     => 'images/courses/lessons',
        'topic_image'      => 'images/courses/lessons/topics',
        'assignment_image' => 'images/courses/assignments',
        'assignment_answer_image' => 'images/courses/assignments/answers',
        'lesson_assignment_image' => 'images/lessons/assignments',
        'lesson_comment_image' => 'images/courses/lessons/comments',
        'question_image'   => 'images/courses/lessons/questions',
        'option_image'     => 'images/courses/lessons/questions/options',
        'quiz_question_image' => 'images/courses/quizzes/questions',
        'quiz_option_image'   => 'images/courses/quizzes/questions/options',
    ];

    /**
     * Copy a file from source to a new filename in the same directory.
     */
    public function copyFile(string $filename, string $targetPath): ?string
    {
        if (empty($filename)) {
            return null;
        }

        // Clean filename in case it contains path components
        $basename = basename($filename);
        $sourcePath = $targetPath . '/' . $basename;

        if (!Storage::disk('public')->exists($sourcePath)) {
            Log::warning("Media file not found: {$sourcePath}");
            return null;
        }

        $extension = pathinfo($basename, PATHINFO_EXTENSION);
        $newFilename = uniqid() . '.' . $extension;
        $destinationPath = $targetPath . '/' . $newFilename;

        try {
            Storage::disk('public')->copy($sourcePath, $destinationPath);
            return $newFilename;
        } catch (\Exception $e) {
            Log::error("Failed to copy media file: {$sourcePath} to {$destinationPath}. Error: " . $e->getMessage());
            return null;
        }
    }

    public function copyCourseCover(?string $filename): ?string
    {
        return $this->copyFile($filename, self::PATHS['course_cover']);
    }

    public function copyCourseLogo(?string $filename): ?string
    {
        return $this->copyFile($filename, self::PATHS['course_logo']);
    }

    public function copyLessonImage(?string $filename): ?string
    {
        return $this->copyFile($filename, self::PATHS['lesson_image']);
    }

    public function copyTopicImage(?string $filename): ?string
    {
        return $this->copyFile($filename, self::PATHS['topic_image']);
    }

    public function copyAssignmentImage(?string $filename): ?string
    {
        return $this->copyFile($filename, self::PATHS['assignment_image']);
    }

    /**
     * Question images can be in multiple locations. We'll try to find it.
     */
    public function copyQuestionImage(?string $filename): ?string
    {
        if (empty($filename)) return null;
        
        $filename = basename($filename);

        // Try standard path
        $newFile = $this->copyFile($filename, self::PATHS['question_image']);
        if ($newFile) return $newFile;

        // Try quiz path
        return $this->copyFile($filename, self::PATHS['quiz_question_image']);
    }

    public function copyOptionImage(?string $filename): ?string
    {
        if (empty($filename)) return null;
        
        $filename = basename($filename);

        // Try standard path
        $newFile = $this->copyFile($filename, self::PATHS['option_image']);
        if ($newFile) return $newFile;

        // Try quiz path
        return $this->copyFile($filename, self::PATHS['quiz_option_image']);
    }

    /**
     * Standardized delete unused helper
     */
    public function deleteUnused(string $pathKey, string $modelClass, string $field, mixed $value, ?int $excludeId = null): void
    {
        if (empty($value)) return;
        
        $path = self::PATHS[$pathKey] ?? $pathKey;
        $filename = basename($value);
        $diskPath = $path . '/' . $filename;

        $this->deleteIfUnused($diskPath, $modelClass, $field, $value, $excludeId);
    }

    /**
     * Delete a file only if it is not used by any other record.
     */
    public function deleteIfUnused(string $diskPath, string $modelClass, string $field, mixed $value, ?int $excludeId = null): void
    {
        if (empty($value)) {
            return;
        }

        $query = $modelClass::where($field, $value);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $exists = $query->exists();

        if (!$exists) {
            if (Storage::disk('public')->exists($diskPath)) {
                Storage::disk('public')->delete($diskPath);
            }
        } else {
            Log::info("File not deleted because it is still used by other records: {$diskPath}");
        }
    }
}
