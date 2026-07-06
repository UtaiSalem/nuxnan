<?php

namespace App\Services;

use App\Models\CourseQuiz;
use App\Models\CourseQuizResult;

class QuizEfficiencyService
{
    /**
     * Calculate quiz efficiency score.
     * Logic: (Score / Total Score) * 100
     * You can expand this to include duration factors later.
     *
     * @return float
     */
    public function calculateEfficiency(CourseQuizResult $result, CourseQuiz $quiz)
    {
        if ($quiz->total_score <= 0) {
            return 0;
        }

        // Basic efficiency: Percentage of correct answers relative to total score
        // We can add time factors later if needed.
        $efficiency = ($result->score / $quiz->total_score) * 100;

        return round($efficiency, 2);
    }
}
