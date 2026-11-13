<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;

/**
 * Single place that creates lessons and topics so every entry point
 * (manual form, .docx import) keeps the same side effects:
 * - course.lessons counter is incremented per lesson
 * - lesson.min_read accumulates each topic's min_read
 */
class LessonCreationService
{
    public function createLesson(Course $course, User $user, array $attrs): Lesson
    {
        $lesson = $course->courseLessons()->create(array_merge($attrs, ['user_id' => $user->id]));

        $course->increment('lessons');

        return $lesson;
    }

    public function createTopic(Lesson $lesson, User $user, array $attrs): Topic
    {
        $topic = $lesson->topics()->create(array_merge($attrs, [
            'user_id' => $user->id,
            'academy_id' => $lesson->course->academy_id,
            'course_id' => $lesson->course_id,
        ]));

        $lesson->increment('min_read', (int) $topic->min_read);

        return $topic;
    }
}
