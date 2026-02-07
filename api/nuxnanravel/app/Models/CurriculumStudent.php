<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CurriculumStudent extends Model
{
    use HasFactory;

    protected $table = 'curriculum_students';

    protected $fillable = [
        'curriculum_id',
        'user_id',
        'academy_member_id',
        'current_year_level',
        'status',
        'enrolled_at',
        'expected_graduation',
        'graduated_at',
        'completed_credits',
    ];

    protected $casts = [
        'current_year_level' => 'integer',
        'enrolled_at' => 'date',
        'expected_graduation' => 'date',
        'graduated_at' => 'date',
        'completed_credits' => 'integer',
    ];

    /**
     * Student status constants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_GRADUATED = 'graduated';
    public const STATUS_DROPPED = 'dropped';
    public const STATUS_SUSPENDED = 'suspended';

    /**
     * หลักสูตร
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    /**
     * ผู้ใช้ (นักเรียน)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * สมาชิก Academy
     */
    public function academyMember(): BelongsTo
    {
        return $this->belongsTo(AcademyMember::class);
    }

    /**
     * ชื่อสถานะภาษาไทย
     */
    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'กำลังศึกษา',
            self::STATUS_GRADUATED => 'สำเร็จการศึกษา',
            self::STATUS_DROPPED => 'พ้นสภาพ',
            self::STATUS_SUSPENDED => 'พักการเรียน',
            default => $this->status,
        };
    }

    /**
     * คำนวณ % ความคืบหน้า
     */
    public function getProgressPercentageAttribute(): float
    {
        $totalCredits = $this->curriculum->total_credits ?? 0;
        if ($totalCredits === 0) return 0;
        
        return round(($this->completed_credits / $totalCredits) * 100, 2);
    }

    /**
     * หน่วยกิตที่เหลือ
     */
    public function getRemainingCreditsAttribute(): int
    {
        $totalCredits = $this->curriculum->total_credits ?? 0;
        return max(0, $totalCredits - $this->completed_credits);
    }

    /**
     * Scope: นักเรียนที่กำลังเรียน
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: นักเรียนที่จบแล้ว
     */
    public function scopeGraduated($query)
    {
        return $query->where('status', self::STATUS_GRADUATED);
    }

    /**
     * Scope: นักเรียนตามชั้นปี
     */
    public function scopeYearLevel($query, $level)
    {
        return $query->where('current_year_level', $level);
    }

    /**
     * จบการศึกษา
     */
    public function graduate(): void
    {
        $this->update([
            'status' => self::STATUS_GRADUATED,
            'graduated_at' => now(),
        ]);
    }

    /**
     * พ้นสภาพ
     */
    public function drop(): void
    {
        $this->update([
            'status' => self::STATUS_DROPPED,
        ]);
    }

    /**
     * พักการเรียน
     */
    public function suspend(): void
    {
        $this->update([
            'status' => self::STATUS_SUSPENDED,
        ]);
    }

    /**
     * กลับมาเรียน
     */
    public function reactivate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
        ]);
    }
}
