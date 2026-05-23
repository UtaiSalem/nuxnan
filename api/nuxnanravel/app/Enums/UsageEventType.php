<?php

namespace App\Enums;

/**
 * UsageEventType Enum
 * 
 * Defines all possible usage event types for gamification and tracking.
 */
enum UsageEventType: string
{
    case LOGIN = 'login';
    case QUIZ_SUBMIT = 'quiz_submit';
    case QUIZ_PASS = 'quiz_pass';
    case LESSON_COMPLETE = 'lesson_complete';
    case LESSON_START = 'lesson_start';
    case ASSIGNMENT_SUBMIT = 'assignment_submit';
    case ASSIGNMENT_GRADED = 'assignment_graded';
    case COURSE_JOIN = 'course_join';
    case POST_CREATE = 'post_create';
    case COMMENT_CREATE = 'comment_create';
    case VIDEO_PROGRESS_25 = 'video_progress_25';
    case VIDEO_PROGRESS_50 = 'video_progress_50';
    case VIDEO_PROGRESS_75 = 'video_progress_75';
    case VIDEO_PROGRESS_100 = 'video_progress_100';

    /**
     * Map of event types to their idempotency strategies.
     * Strategies: daily, once_per_source, daily_per_source, always
     */
    public const IDEMPOTENCY_STRATEGIES = [
        'login' => 'daily',
        'quiz_submit' => 'always',
        'quiz_pass' => 'once_per_source',
        'lesson_complete' => 'once_per_source',
        'lesson_start' => 'once_per_source',
        'assignment_submit' => 'once_per_source',
        'assignment_graded' => 'once_per_source',
        'course_join' => 'once_per_source',
        'post_create' => 'always',
        'comment_create' => 'always',
        'video_progress_25' => 'once_per_source',
        'video_progress_50' => 'once_per_source',
        'video_progress_75' => 'once_per_source',
        'video_progress_100' => 'once_per_source',
    ];

    /**
     * Get the idempotency strategy for this event type.
     */
    public function strategy(): string
    {
        return self::IDEMPOTENCY_STRATEGIES[$this->value] ?? 'daily';
    }
}
