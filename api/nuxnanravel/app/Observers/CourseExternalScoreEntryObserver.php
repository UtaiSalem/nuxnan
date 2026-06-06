<?php

namespace App\Observers;

use App\Models\CourseExternalScoreEntry;
use App\Services\CourseScoreService;

class CourseExternalScoreEntryObserver
{
    protected CourseScoreService $scoreService;

    public function __construct(CourseScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    public function saved(CourseExternalScoreEntry $entry): void
    {
        if ($entry->courseMember) {
            $this->scoreService->recompute($entry->courseMember);
        }
    }

    public function deleted(CourseExternalScoreEntry $entry): void
    {
        if ($entry->courseMember) {
            $this->scoreService->recompute($entry->courseMember);
        }
    }
}
