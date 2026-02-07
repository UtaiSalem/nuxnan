<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Subject Model - รายวิชา
 */
class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'subject_code',
        'name_th',
        'name_en',
        'description',
        'credits',
        'hours_per_week',
        'subject_type',
        'subject_group',
        'grade_levels',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'credits' => 'decimal:1',
        'hours_per_week' => 'decimal:1',
        'grade_levels' => 'array',
        'is_active' => 'boolean',
    ];

    // Subject Types
    const TYPE_REQUIRED = 'required';
    const TYPE_ELECTIVE = 'elective';
    const TYPE_ACTIVITY = 'activity';

    // Subject Groups
    const GROUPS = [
        'thai' => 'ภาษาไทย',
        'math' => 'คณิตศาสตร์',
        'science' => 'วิทยาศาสตร์และเทคโนโลยี',
        'social' => 'สังคมศึกษา ศาสนา และวัฒนธรรม',
        'health' => 'สุขศึกษาและพลศึกษา',
        'art' => 'ศิลปะ',
        'career' => 'การงานอาชีพ',
        'foreign' => 'ภาษาต่างประเทศ',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('subject_type', $type);
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('subject_group', $group);
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return $this->subject_code 
            ? "{$this->subject_code} - {$this->name_th}" 
            : $this->name_th;
    }

    public function getSubjectTypeTextAttribute(): string
    {
        return match($this->subject_type) {
            self::TYPE_REQUIRED => 'วิชาบังคับ',
            self::TYPE_ELECTIVE => 'วิชาเลือก',
            self::TYPE_ACTIVITY => 'กิจกรรมพัฒนาผู้เรียน',
            default => 'อื่นๆ'
        };
    }

    public function getSubjectGroupTextAttribute(): string
    {
        return self::GROUPS[$this->subject_group] ?? $this->subject_group ?? 'ไม่ระบุ';
    }
}
