<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Semester Model - ภาคเรียน
 */
class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'semester_number',
        'name',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'semester_number' => 'integer',
    ];

    // Semester Numbers
    const SEMESTER_1 = 1;
    const SEMESTER_2 = 2;
    const SEMESTER_SUMMER = 3;

    // Relationships
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function gradebookAssessments(): HasMany
    {
        return $this->hasMany(GradebookAssessment::class);
    }

    public function courseGrades(): HasMany
    {
        return $this->hasMany(CourseGrade::class);
    }

    public function semesterTranscripts(): HasMany
    {
        return $this->hasMany(SemesterTranscript::class);
    }

    // Scopes
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    // Methods
    public function setAsCurrent(): void
    {
        // Remove current status from other semesters in same academic year
        self::where('academic_year_id', $this->academic_year_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);

        $this->update(['is_current' => true]);
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        $yearName = $this->academicYear?->name ?? '';
        return "{$this->name} ปีการศึกษา {$yearName}";
    }

    public function getShortNameAttribute(): string
    {
        return match($this->semester_number) {
            self::SEMESTER_1 => 'เทอม 1',
            self::SEMESTER_2 => 'เทอม 2',
            self::SEMESTER_SUMMER => 'ฤดูร้อน',
            default => $this->name
        };
    }

    public function getDurationAttribute(): string
    {
        return $this->start_date->format('d/m/Y') . ' - ' . $this->end_date->format('d/m/Y');
    }

    public function getIsActiveAttribute(): bool
    {
        $now = now();
        return $now->between($this->start_date, $this->end_date);
    }
}
