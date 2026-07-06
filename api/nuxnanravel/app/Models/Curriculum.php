<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curriculum extends Model
{
    use HasFactory;

    protected $table = 'curriculums';

    protected $fillable = [
        'academy_id',
        'name',
        'code',
        'description',
        'level',
        'academic_year',
        'duration_years',
        'total_credits',
        'required_credits',
        'elective_credits',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'duration_years' => 'integer',
        'total_credits' => 'integer',
        'required_credits' => 'integer',
        'elective_credits' => 'integer',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Academy ที่เป็นเจ้าของหลักสูตร
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * รายวิชาในหลักสูตร (pivot)
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'curriculum_courses')
            ->withPivot([
                'course_type',
                'semester',
                'year_level',
                'credits',
                'sort_order',
                'is_required',
                'prerequisites',
            ])
            ->withTimestamps();
    }

    /**
     * รายวิชาในหลักสูตร (has many through pivot model)
     */
    public function curriculumCourses(): HasMany
    {
        return $this->hasMany(CurriculumCourse::class);
    }

    /**
     * นักเรียนในหลักสูตร
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'curriculum_students')
            ->withPivot([
                'academy_member_id',
                'current_year_level',
                'status',
                'enrolled_at',
                'expected_graduation',
                'graduated_at',
                'completed_credits',
            ])
            ->withTimestamps();
    }

    /**
     * นักเรียนในหลักสูตร (has many through pivot model)
     */
    public function curriculumStudents(): HasMany
    {
        return $this->hasMany(CurriculumStudent::class);
    }

    /**
     * รายวิชาบังคับ
     */
    public function requiredCourses(): BelongsToMany
    {
        return $this->courses()->wherePivot('course_type', 'required');
    }

    /**
     * รายวิชาเลือก
     */
    public function electiveCourses(): BelongsToMany
    {
        return $this->courses()->wherePivot('course_type', 'elective');
    }

    /**
     * รายวิชาพื้นฐาน
     */
    public function generalCourses(): BelongsToMany
    {
        return $this->courses()->wherePivot('course_type', 'general');
    }

    /**
     * นับจำนวนรายวิชา
     */
    public function getCoursesCountAttribute(): int
    {
        return $this->curriculumCourses()->count();
    }

    /**
     * นับจำนวนนักเรียนที่กำลังเรียน
     */
    public function getActiveStudentsCountAttribute(): int
    {
        return $this->curriculumStudents()->where('status', 'active')->count();
    }

    /**
     * Scope: หลักสูตรที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: หลักสูตรของ Academy
     */
    public function scopeForAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * Scope: หลักสูตรตามปีการศึกษา
     */
    public function scopeAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }
}
