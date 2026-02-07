<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Classroom Model - ห้องเรียน
 */
class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'academic_year_id',
        'grade_level',
        'section',
        'name',
        'homeroom_teacher_id',
        'room_location',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    public function classroomStudents(): HasMany
    {
        return $this->hasMany(ClassroomStudent::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'classroom_students')
            ->withPivot('student_number', 'status')
            ->withTimestamps();
    }

    public function activeStudents(): BelongsToMany
    {
        return $this->students()->wherePivot('status', 'active');
    }

    public function semesterTranscripts(): HasMany
    {
        return $this->hasMany(SemesterTranscript::class);
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

    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeByGradeLevel($query, $gradeLevel)
    {
        return $query->where('grade_level', $gradeLevel);
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? "{$this->grade_level}/{$this->section}";
    }

    public function getStudentCountAttribute(): int
    {
        return $this->activeStudents()->count();
    }

    public function getFullNameAttribute(): string
    {
        $yearName = $this->academicYear?->name ?? '';
        return "{$this->display_name} ปีการศึกษา {$yearName}";
    }

    // Methods
    public function addStudent(Student $student, ?int $studentNumber = null): ClassroomStudent
    {
        // Get next student number if not provided
        if ($studentNumber === null) {
            $studentNumber = $this->classroomStudents()
                ->where('status', 'active')
                ->max('student_number') + 1;
        }

        return $this->classroomStudents()->updateOrCreate(
            ['student_id' => $student->id],
            [
                'student_number' => $studentNumber,
                'status' => 'active',
            ]
        );
    }

    public function removeStudent(Student $student): bool
    {
        return $this->classroomStudents()
            ->where('student_id', $student->id)
            ->update(['status' => 'transferred']);
    }
}
