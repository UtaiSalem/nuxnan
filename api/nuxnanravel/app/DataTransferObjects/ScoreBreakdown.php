<?php

namespace App\DataTransferObjects;

class ScoreBreakdown
{
    public float $courseQuizEarned = 0;
    public float $courseQuizMax = 0;
    public float $lessonQuestionEarned = 0;
    public float $lessonQuestionMax = 0;
    public float $courseAssignmentEarned = 0;
    public float $courseAssignmentMax = 0;
    public float $lessonAssignmentEarned = 0;
    public float $lessonAssignmentMax = 0;
    public float $externalEarned = 0;
    public float $externalMax = 0;
    public float $bonus = 0; // signed
    public array $missingSources = []; // ['quiz_ids'=>[], 'assignment_ids'=>[], 'lesson_ids'=>[]]

    public function totalEarned(): float
    {
        return $this->internalEarned() + $this->externalEarned + $this->bonus;
    }
    
    public function internalEarned(): float
    {
        return $this->courseQuizEarned + $this->lessonQuestionEarned + $this->courseAssignmentEarned + $this->lessonAssignmentEarned;
    }

    public function totalMax(): float
    {
        return $this->courseQuizMax + $this->lessonQuestionMax + $this->courseAssignmentMax + $this->lessonAssignmentMax + $this->externalMax;
    }

    public function percentage(): float
    {
        $max = $this->totalMax();
        if ($max <= 0) {
            return 0; // Avoid division by zero
        }
        
        $percentage = ($this->totalEarned() / $max) * 100;
        
        // Cap at 100%, and bottom at 0% since penalty max is -100% of max score.
        return min(100, max(0, $percentage));
    }

    public function toArray(): array
    {
        return [
            'course_quiz' => [
                'earned' => $this->courseQuizEarned,
                'max' => $this->courseQuizMax,
            ],
            'lesson_question' => [
                'earned' => $this->lessonQuestionEarned,
                'max' => $this->lessonQuestionMax,
            ],
            'course_assignment' => [
                'earned' => $this->courseAssignmentEarned,
                'max' => $this->courseAssignmentMax,
            ],
            'lesson_assignment' => [
                'earned' => $this->lessonAssignmentEarned,
                'max' => $this->lessonAssignmentMax,
            ],
            'external' => [
                'earned' => $this->externalEarned,
                'max' => $this->externalMax,
            ],
            'bonus' => $this->bonus,
            'total_earned' => $this->totalEarned(),
            'internal_earned' => $this->internalEarned(),
            'total_max' => $this->totalMax(),
            'percentage' => round($this->percentage(), 2),
            'missing_sources' => $this->missingSources,
        ];
    }
}
