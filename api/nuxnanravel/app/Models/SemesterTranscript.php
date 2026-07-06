<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SemesterTranscript Model - ผลการเรียนรายภาค
 */
class SemesterTranscript extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academy_id',
        'semester_id',
        'classroom_id',
        'total_credits',
        'earned_credits',
        'gpa',
        'class_rank',
        'total_students_in_class',
        'grade_rank',
        'total_students_in_grade',
        'remarks',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'total_credits' => 'decimal:2',
        'earned_credits' => 'decimal:2',
        'gpa' => 'decimal:2',
        'class_rank' => 'integer',
        'total_students_in_class' => 'integer',
        'grade_rank' => 'integer',
        'total_students_in_grade' => 'integer',
        'approved_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_DRAFT = 'draft';

    const STATUS_PUBLISHED = 'published';

    const STATUS_APPROVED = 'approved';

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SemesterTranscriptItem::class, 'transcript_id');
    }

    // Scopes
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeBySemester($query, $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    public function scopePublished($query)
    {
        return $query->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_APPROVED]);
    }

    // Accessors
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'ร่าง',
            self::STATUS_PUBLISHED => 'เผยแพร่',
            self::STATUS_APPROVED => 'อนุมัติ',
            default => 'ไม่ระบุ'
        };
    }

    public function getClassRankDisplayAttribute(): string
    {
        if (! $this->class_rank) {
            return '-';
        }

        return "{$this->class_rank}/{$this->total_students_in_class}";
    }

    public function getGradeRankDisplayAttribute(): string
    {
        if (! $this->grade_rank) {
            return '-';
        }

        return "{$this->grade_rank}/{$this->total_students_in_grade}";
    }

    public function getGpaDisplayAttribute(): string
    {
        return $this->gpa !== null ? number_format($this->gpa, 2) : '-';
    }

    // Methods
    /**
     * Calculate GPA and credits from course grades
     */
    public function calculate(): self
    {
        $courseGrades = CourseGrade::where('student_id', $this->student_id)
            ->where('semester_id', $this->semester_id)
            ->where('status', CourseGrade::STATUS_COMPLETED)
            ->with('course')
            ->get();

        $totalCredits = 0;
        $earnedCredits = 0;
        $totalPoints = 0;

        foreach ($courseGrades as $grade) {
            $credits = $grade->course?->credit_units ?? 1;
            $totalCredits += $credits;

            if ($grade->grade_points >= 1) {
                $earnedCredits += $credits;
            }

            $totalPoints += ($grade->grade_points ?? 0) * $credits;

            // Create transcript item
            $this->items()->updateOrCreate(
                ['course_id' => $grade->course_id],
                [
                    'subject_id' => $grade->course?->subject_id,
                    'subject_code' => $grade->course?->code ?? '',
                    'subject_name' => $grade->course?->name ?? '',
                    'credits' => $credits,
                    'score' => $grade->percentage,
                    'letter_grade' => $grade->letter_grade,
                    'grade_points' => $grade->grade_points,
                ]
            );
        }

        $gpa = $totalCredits > 0 ? $totalPoints / $totalCredits : 0;

        $this->update([
            'total_credits' => $totalCredits,
            'earned_credits' => $earnedCredits,
            'gpa' => round($gpa, 2),
        ]);

        return $this;
    }

    /**
     * Calculate class ranking
     */
    public function calculateRanking(): self
    {
        if (! $this->classroom_id || ! $this->gpa) {
            return $this;
        }

        // Class ranking
        $classRank = self::where('classroom_id', $this->classroom_id)
            ->where('semester_id', $this->semester_id)
            ->where('gpa', '>', $this->gpa)
            ->count() + 1;

        $totalInClass = self::where('classroom_id', $this->classroom_id)
            ->where('semester_id', $this->semester_id)
            ->count();

        // Grade level ranking
        $gradeLevel = $this->classroom?->grade_level;
        $academicYearId = $this->semester?->academic_year_id;

        $gradeRank = null;
        $totalInGrade = null;

        if ($gradeLevel && $academicYearId) {
            $classroomIds = Classroom::where('academy_id', $this->academy_id)
                ->where('academic_year_id', $academicYearId)
                ->where('grade_level', $gradeLevel)
                ->pluck('id');

            $gradeRank = self::whereIn('classroom_id', $classroomIds)
                ->where('semester_id', $this->semester_id)
                ->where('gpa', '>', $this->gpa)
                ->count() + 1;

            $totalInGrade = self::whereIn('classroom_id', $classroomIds)
                ->where('semester_id', $this->semester_id)
                ->count();
        }

        $this->update([
            'class_rank' => $classRank,
            'total_students_in_class' => $totalInClass,
            'grade_rank' => $gradeRank,
            'total_students_in_grade' => $totalInGrade,
        ]);

        return $this;
    }

    /**
     * Publish transcript
     */
    public function publish(): self
    {
        $this->update(['status' => self::STATUS_PUBLISHED]);

        return $this;
    }

    /**
     * Approve transcript
     */
    public function approve(?int $approverId = null): self
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approverId ?? auth()->id(),
            'approved_at' => now(),
        ]);

        return $this;
    }
}
