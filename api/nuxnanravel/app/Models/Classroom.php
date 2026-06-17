<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

// Related classroom management models
use App\Models\ClassroomMember;
use App\Models\ClassroomGroup;
use App\Models\ClassroomInvitation;
use App\Traits\Auditable;

/**
 * Classroom Model - ห้องเรียน
 */
class Classroom extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'academy_id',
        'academic_year_id',
        'classroom_code',
        'grade_level',
        'section',
        'name',
        'academic_year',
        'semester',
        'homeroom_teacher_id',
        'room_location',
        'capacity',
        'is_active',
        'status',
        'created_by',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'semester' => 'integer',
        'is_active' => 'boolean',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';

    // ───── Boot ─────
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Classroom $classroom) {
            if (empty($classroom->classroom_code)) {
                $classroom->classroom_code = self::generateUniqueCode();
            }
        });
    }

    /**
     * Generate a unique 6-char alphanumeric code.
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('classroom_code', $code)->exists());

        return $code;
    }

    // ───── Relationships ─────

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Legacy relationship — kept for backward compatibility
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

    // New membership system
    public function classroomMembers(): HasMany
    {
        return $this->hasMany(ClassroomMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->classroomMembers()->where('is_active', true);
    }

    public function memberUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'classroom_members')
            ->withPivot('role', 'student_no', 'join_method', 'is_active', 'joined_at', 'left_at', 'added_by')
            ->withTimestamps();
    }

    public function classroomGroups(): HasMany
    {
        return $this->hasMany(ClassroomGroup::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ClassroomInvitation::class);
    }

    public function semesterTranscripts(): HasMany
    {
        return $this->hasMany(SemesterTranscript::class);
    }

    /**
     * Students enrolled in this classroom via classroom_students pivot table.
     * This is the source of truth for classroom enrollment.
     *
     * For backward compatibility, also falls back to direct field matching
     * if no pivot records exist.
     */
    public function getEnrolledStudentsQuery()
    {
        $pivotCount = $this->classroomStudents()->active()->count();

        if ($pivotCount > 0) {
            // Use pivot table (new system — source of truth)
            return Student::whereIn('id', function ($query) {
                $query->select('student_id')
                    ->from('classroom_students')
                    ->where('classroom_id', $this->id)
                    ->where('status', 'active');
            });
        }

        // Fallback: direct field matching (legacy — will be deprecated)
        return Student::where('class_level', $this->grade_level)
            ->where('class_section', $this->section)
            ->where('academy_id', $this->academy_id);
    }

    /**
     * Scope to add student_count via classroom_students pivot (preferred).
     * Falls back to direct field matching if pivot is empty.
     */
    public function scopeWithStudentCount($query)
    {
        return $query->addSelect([
            'student_count' => ClassroomStudent::selectRaw('COUNT(*)')
                ->whereColumn('classroom_students.classroom_id', 'classrooms.id')
                ->where('classroom_students.status', 'active'),
        ]);
    }

    // ───── Scopes ─────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
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

    // ───── Accessors ─────

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? "{$this->grade_level}/{$this->section}";
    }

    public function getStudentCountAttribute(): int
    {
        // If already loaded via withStudentCount scope, use that
        if (array_key_exists('student_count', $this->attributes)) {
            return (int) $this->attributes['student_count'];
        }
        return $this->classroomStudents()->active()->count();
    }

    public function getActiveMemberCountAttribute(): int
    {
        return $this->activeMembers()->count();
    }

    public function getFullNameAttribute(): string
    {
        $yearName = $this->academic_year ?? $this->academicYear?->name ?? '';
        return "{$this->display_name} ปีการศึกษา {$yearName}";
    }

    // ───── Helper Methods ─────

    public function addStudent(Student $student, ?int $studentNumber = null): ClassroomStudent
    {
        if ($studentNumber === null) {
            $studentNumber = $this->classroomStudents()
                ->where('status', 'active')
                ->max('student_number') + 1;
        }

        $record = $this->classroomStudents()->updateOrCreate(
            ['student_id' => $student->id],
            [
                'academy_id' => $this->academy_id,
                'academic_year_id' => $this->academic_year_id,
                'student_number' => $studentNumber,
                'status' => 'active',
                'enrolled_at' => now(),
            ]
        );

        // Sync class_level & class_section to student for backward compatibility
        $student->update([
            'class_level' => $this->grade_level,
            'class_section' => $this->section,
        ]);

        return $record;
    }

    public function removeStudent(Student $student): bool
    {
        return $this->classroomStudents()
            ->where('student_id', $student->id)
            ->update([
                'status' => 'transferred',
                'left_at' => now(),
            ]);
    }

    /**
     * Check if classroom has reached capacity.
     */
    public function isFull(): bool
    {
        if (!$this->capacity) {
            return false;
        }

        return $this->classroomStudents()->active()->count() >= $this->capacity;
    }
}
