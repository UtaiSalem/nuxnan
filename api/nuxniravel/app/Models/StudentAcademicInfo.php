<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Student Academic Information Model
 */
class StudentAcademicInfo extends Model
{
    use HasFactory;

    protected $table = 'student_academic_info';

    protected $fillable = [
        'student_id',
        'current_grade',
        'education_level',
        'current_class',
        'classroom_full',
        'school_name',
        'school_address',
        'school_province',
        'previous_school_name',
        'previous_school_province',
        'previous_grade_level',
        'disability_type',
        'special_needs',
        'academic_year',
        'semester',
        'enrollment_date',
        'graduation_date',
        'study_status',
        'is_current',
        'transfer_reason',
        'notes'
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'graduation_date' => 'date',
        'is_current' => 'boolean',
        'semester' => 'integer'
    ];

    // Study status constants
    const STATUS_STUDYING = 'studying';
    const STATUS_GRADUATED = 'graduated';
    const STATUS_TRANSFERRED = 'transferred';
    const STATUS_DROPPED = 'dropped';
    const STATUS_SUSPENDED = 'suspended';
    
    // Education level constants
    const EDUCATION_KINDERGARTEN = 0;
    const EDUCATION_PRIMARY = 1;
    const EDUCATION_SECONDARY = 2;

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getFullClassroomAttribute(): string
    {
        if ($this->classroom_full) {
            return $this->classroom_full;
        }
        
        return "{$this->current_grade}/{$this->current_class}";
    }

    /**
     * Get education level text
     */
    public function getEducationLevelTextAttribute(): string
    {
        return match($this->education_level) {
            self::EDUCATION_KINDERGARTEN => 'อนุบาล',
            self::EDUCATION_PRIMARY => 'ประถม',
            self::EDUCATION_SECONDARY => 'มัธยม',
            default => 'ไม่ระบุ'
        };
    }

    /**
     * Static method to get education level from string
     */
    public static function getEducationLevelFromString(string $level): int
    {
        return match(strtolower($level)) {
            'kindergarten', 'อนุบาล' => self::EDUCATION_KINDERGARTEN,
            'primary', 'ประถม' => self::EDUCATION_PRIMARY,
            'secondary', 'มัธยม' => self::EDUCATION_SECONDARY,
            default => self::EDUCATION_SECONDARY
        };
    }

    /**
     * Check if this academic info is for current academic year
     */
    public function isCurrentAcademicYear(): bool
    {
        if (!$this->academic_year) {
            return false;
        }

        $currentYear = $this->getCurrentAcademicYear();
        return $this->academic_year == $currentYear;
    }

    /**
     * Get current academic year in Buddhist calendar
     */
    public static function getCurrentAcademicYear(): string
    {
        $now = new \DateTime();
        $buddhistYear = $now->format('Y') + 543;
        
        // Academic year starts in May of the previous year
        if ($now->format('n') < 5) { // January-April = semester 2
            return (string)($buddhistYear - 1);
        }
        return (string)$buddhistYear; // May-December = semester 1 of new academic year
    }

    /**
     * Get current semester
     */
    public static function getCurrentSemester(): int
    {
        $now = new \DateTime();
        $month = (int)$now->format('n');
        
        // Semester 1: May-September
        // Semester 2: October-April
        if ($month >= 5 && $month <= 9) {
            return 1;
        }
        return 2;
    }

    /**
     * Scope for current academic year
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('academic_year', self::getCurrentAcademicYear());
    }

    /**
     * Scope for specific grade level
     */
    public function scopeByGrade($query, $grade)
    {
        return $query->where('current_grade', $grade);
    }

    /**
     * Scope for specific class
     */
    public function scopeByClass($query, $grade, $class)
    {
        return $query->where('current_grade', $grade)
                    ->where('current_class', $class);
    }

    /**
     * Scope for students with disabilities
     */
    public function scopeWithDisabilities($query)
    {
        return $query->whereNotNull('disability_type');
    }

    /**
     * Scope for students with special needs
     */
    public function scopeWithSpecialNeeds($query)
    {
        return $query->whereNotNull('special_needs');
    }

    /**
     * Scope for transfer students
     */
    public function scopeTransferStudents($query)
    {
        return $query->whereNotNull('previous_school_name');
    }

    /**
     * Scope for current academic records
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope by study status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('study_status', $status);
    }

    /**
     * Scope for studying students
     */
    public function scopeStudying($query)
    {
        return $query->where('study_status', self::STATUS_STUDYING);
    }

    /**
     * Scope for graduated students
     */
    public function scopeGraduated($query)
    {
        return $query->where('study_status', self::STATUS_GRADUATED);
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->study_status) {
            self::STATUS_STUDYING => 'กำลังศึกษา',
            self::STATUS_GRADUATED => 'จบการศึกษา',
            self::STATUS_TRANSFERRED => 'ย้ายโรงเรียน',
            self::STATUS_DROPPED => 'ออกจากระบบ',
            self::STATUS_SUSPENDED => 'พักการเรียน',
            default => 'ไม่ระบุ'
        };
    }

    /**
     * Check if this record is active/current
     */
    public function isCurrent(): bool
    {
        return $this->is_current === true;
    }

    /**
     * Set as current academic record (and unset others for same student)
     */
    public function setAsCurrent(): void
    {
        // Remove current flag from all other records of this student
        self::where('student_id', $this->student_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);
        
        // Set this record as current
        $this->update(['is_current' => true]);
    }

    /**
     * Get academic period display text
     */
    public function getAcademicPeriodAttribute(): string
    {
        return "{$this->academic_year}/{$this->semester}";
    }
}
