<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AnnualTranscript Model - ผลการเรียนรายปี (GPAX)
 */
class AnnualTranscript extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academy_id',
        'academic_year_id',
        'grade_level',
        'total_credits',
        'earned_credits',
        'gpax',
        'class_rank',
        'grade_rank',
        'promotion_status',
        'remarks',
        'status',
    ];

    protected $casts = [
        'total_credits' => 'decimal:2',
        'earned_credits' => 'decimal:2',
        'gpax' => 'decimal:2',
        'class_rank' => 'integer',
        'grade_rank' => 'integer',
    ];

    // Promotion Status Constants
    const PROMOTION_PROMOTED = 'promoted';

    const PROMOTION_RETAINED = 'retained';

    const PROMOTION_CONDITIONAL = 'conditional';

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

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
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

    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    // Accessors
    public function getPromotionStatusTextAttribute(): string
    {
        return match ($this->promotion_status) {
            self::PROMOTION_PROMOTED => 'เลื่อนชั้น',
            self::PROMOTION_RETAINED => 'ซ้ำชั้น',
            self::PROMOTION_CONDITIONAL => 'มีเงื่อนไข',
            default => 'ไม่ระบุ'
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'ร่าง',
            self::STATUS_PUBLISHED => 'เผยแพร่',
            self::STATUS_APPROVED => 'อนุมัติ',
            default => 'ไม่ระบุ'
        };
    }

    public function getGpaxDisplayAttribute(): string
    {
        return $this->gpax !== null ? number_format($this->gpax, 2) : '-';
    }

    // Methods
    /**
     * Calculate GPAX from semester transcripts
     */
    public function calculate(): self
    {
        $semesterIds = Semester::where('academic_year_id', $this->academic_year_id)
            ->pluck('id');

        $transcripts = SemesterTranscript::where('student_id', $this->student_id)
            ->whereIn('semester_id', $semesterIds)
            ->whereIn('status', [SemesterTranscript::STATUS_PUBLISHED, SemesterTranscript::STATUS_APPROVED])
            ->get();

        $totalCredits = 0;
        $earnedCredits = 0;
        $totalPoints = 0;

        foreach ($transcripts as $transcript) {
            foreach ($transcript->items as $item) {
                $credits = $item->credits ?? 0;
                $totalCredits += $credits;

                if ($item->grade_points >= 1) {
                    $earnedCredits += $credits;
                }

                $totalPoints += ($item->grade_points ?? 0) * $credits;
            }
        }

        $gpax = $totalCredits > 0 ? $totalPoints / $totalCredits : 0;

        $this->update([
            'total_credits' => $totalCredits,
            'earned_credits' => $earnedCredits,
            'gpax' => round($gpax, 2),
        ]);

        return $this;
    }

    /**
     * Determine promotion status
     */
    public function determinePromotionStatus(): self
    {
        $minCreditsPercent = 0.5; // ต้องผ่านอย่างน้อย 50% ของหน่วยกิต
        $minGpax = 1.0; // GPAX ขั้นต่ำ

        if ($this->total_credits == 0) {
            $this->update(['promotion_status' => null]);

            return $this;
        }

        $creditPercentage = $this->earned_credits / $this->total_credits;

        if ($creditPercentage >= $minCreditsPercent && $this->gpax >= $minGpax) {
            $status = self::PROMOTION_PROMOTED;
        } elseif ($this->gpax >= $minGpax || $creditPercentage >= 0.4) {
            $status = self::PROMOTION_CONDITIONAL;
        } else {
            $status = self::PROMOTION_RETAINED;
        }

        $this->update(['promotion_status' => $status]);

        return $this;
    }
}
