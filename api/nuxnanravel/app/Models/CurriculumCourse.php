<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumCourse extends Model
{
    use HasFactory;

    protected $table = 'curriculum_courses';

    protected $fillable = [
        'curriculum_id',
        'course_id',
        'course_type',
        'semester',
        'year_level',
        'credits',
        'sort_order',
        'is_required',
        'prerequisites',
    ];

    protected $casts = [
        'year_level' => 'integer',
        'credits' => 'integer',
        'sort_order' => 'integer',
        'is_required' => 'boolean',
        'prerequisites' => 'array',
    ];

    /**
     * Course types
     */
    public const TYPE_REQUIRED = 'required';   // วิชาบังคับ

    public const TYPE_ELECTIVE = 'elective';   // วิชาเลือก

    public const TYPE_GENERAL = 'general';     // วิชาพื้นฐาน

    /**
     * หลักสูตรที่รายวิชานี้อยู่
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    /**
     * รายวิชา
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * รายวิชาที่ต้องผ่านก่อน
     */
    public function prerequisiteCourses()
    {
        if (empty($this->prerequisites)) {
            return collect();
        }

        return Course::whereIn('id', $this->prerequisites)->get();
    }

    /**
     * ชื่อประเภทรายวิชาภาษาไทย
     */
    public function getCourseTypeNameAttribute(): string
    {
        return match ($this->course_type) {
            self::TYPE_REQUIRED => 'วิชาบังคับ',
            self::TYPE_ELECTIVE => 'วิชาเลือก',
            self::TYPE_GENERAL => 'วิชาพื้นฐาน',
            default => $this->course_type,
        };
    }

    /**
     * Scope: รายวิชาบังคับ
     */
    public function scopeRequired($query)
    {
        return $query->where('course_type', self::TYPE_REQUIRED);
    }

    /**
     * Scope: รายวิชาเลือก
     */
    public function scopeElective($query)
    {
        return $query->where('course_type', self::TYPE_ELECTIVE);
    }

    /**
     * Scope: รายวิชาตามชั้นปี
     */
    public function scopeYearLevel($query, $level)
    {
        return $query->where('year_level', $level);
    }

    /**
     * Scope: รายวิชาตามภาคเรียน
     */
    public function scopeSemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }
}
