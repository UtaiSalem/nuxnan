<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CourseMediaService
{
    /**
     * Paths for various course-related media.
     */
    const PATHS = [
        'course_cover' => 'images/courses/covers',
        'course_logo' => 'images/courses/logos',
        'lesson_image' => 'images/courses/lessons',
        'topic_image' => 'images/courses/lessons/topics',
        'assignment_image' => 'images/courses/assignments',
        'assignment_answer_image' => 'images/courses/assignments/answers',
        'lesson_assignment_image' => 'images/lessons/assignments',
        'lesson_comment_image' => 'images/courses/lessons/comments',
        'question_image' => 'images/courses/lessons/questions',
        'option_image' => 'images/courses/lessons/questions/options',
        'quiz_question_image' => 'images/courses/quizzes/questions',
        'quiz_option_image' => 'images/courses/quizzes/questions/options',
        'legacy_question_image' => 'images/courses/questions',
        'legacy_option_image' => 'images/courses/questions/options',
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
        $sourcePath = $targetPath.'/'.$basename;

        if (! Storage::disk('public')->exists($sourcePath)) {
            Log::warning("Media file not found: {$sourcePath}");

            return null;
        }

        $extension = pathinfo($basename, PATHINFO_EXTENSION);
        $newFilename = uniqid().'.'.$extension;
        $destinationPath = $targetPath.'/'.$newFilename;

        try {
            Storage::disk('public')->copy($sourcePath, $destinationPath);

            return $newFilename;
        } catch (\Exception $e) {
            Log::error("Failed to copy media file: {$sourcePath} to {$destinationPath}. Error: ".$e->getMessage());

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
     * Every directory a question or option image may physically live in.
     *
     * Uploads are not consistent: quiz questions AND their options both land in
     * `images/courses/quizzes/questions` (see QuestionOptionController and
     * CourseQuizQuestionController), while lesson questions use the
     * `lessons/questions` pair. A copy must look in all of them or the image is
     * silently lost. Mirrors QuestionImage::getUrlAttribute().
     */
    const QUESTION_IMAGE_SEARCH_PATHS = [
        'quiz_question_image',
        'question_image',
        'quiz_option_image',
        'option_image',
        'legacy_question_image',
        'legacy_option_image',
    ];

    /**
     * Find the directory that actually holds the file.
     *
     * @param  array<int, string>  $pathKeys  keys of self::PATHS (or raw paths)
     * @return string|null the directory, not the full path
     */
    public function locate(?string $filename, array $pathKeys): ?string
    {
        if (empty($filename)) {
            return null;
        }

        $basename = basename($filename);

        foreach ($pathKeys as $key) {
            $path = self::PATHS[$key] ?? $key;

            if (Storage::disk('public')->exists($path.'/'.$basename)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Copy a file that may live in any of the given directories, into whichever
     * directory it was found in.
     *
     * @param  array<int, string>  $pathKeys  keys of self::PATHS (or raw paths)
     */
    public function copyFileFromAny(?string $filename, array $pathKeys): ?string
    {
        if (empty($filename)) {
            return null;
        }

        $basename = basename($filename);
        $path = $this->locate($basename, $pathKeys);

        if (! $path) {
            Log::warning("Media file not found in any candidate path: {$basename}", [
                'paths' => array_map(fn ($key) => self::PATHS[$key] ?? $key, $pathKeys),
            ]);

            return null;
        }

        return $this->copyFile($basename, $path);
    }

    /**
     * Question images can be in multiple locations. We'll try to find it.
     */
    public function copyQuestionImage(?string $filename): ?string
    {
        return $this->copyFileFromAny($filename, self::QUESTION_IMAGE_SEARCH_PATHS);
    }

    public function copyOptionImage(?string $filename): ?string
    {
        return $this->copyFileFromAny($filename, self::QUESTION_IMAGE_SEARCH_PATHS);
    }

    /**
     * Standardized delete unused helper
     */
    public function deleteUnused(string $pathKey, string $modelClass, string $field, mixed $value, ?int $excludeId = null): void
    {
        if (empty($value)) {
            return;
        }

        $path = self::PATHS[$pathKey] ?? $pathKey;
        $filename = basename($value);
        $diskPath = $path.'/'.$filename;

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

        if (! $exists) {
            if (Storage::disk('public')->exists($diskPath)) {
                Storage::disk('public')->delete($diskPath);
            }
        } else {
            Log::info("File not deleted because it is still used by other records: {$diskPath}");
        }
    }
}
