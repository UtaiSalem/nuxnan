<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SemesterTranscriptItem Model - รายละเอียดผลการเรียนรายวิชา
 */
class SemesterTranscriptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transcript_id',
        'course_id',
        'subject_id',
        'subject_code',
        'subject_name',
        'credits',
        'score',
        'letter_grade',
        'grade_points',
    ];

    protected $casts = [
        'credits' => 'decimal:1',
        'score' => 'decimal:2',
        'grade_points' => 'decimal:2',
    ];

    // Relationships
    public function transcript(): BelongsTo
    {
        return $this->belongsTo(SemesterTranscript::class, 'transcript_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    // Accessors
    public function getDisplayGradeAttribute(): string
    {
        return $this->letter_grade ?? '-';
    }

    public function getGradePointsDisplayAttribute(): string
    {
        return $this->grade_points !== null ? number_format($this->grade_points, 2) : '-';
    }

    public function getIsPassed(): bool
    {
        return $this->grade_points !== null && $this->grade_points >= 1.0;
    }
}
