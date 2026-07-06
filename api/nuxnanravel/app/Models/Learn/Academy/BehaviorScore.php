<?php

namespace App\Models\Learn\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BehaviorScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'student_id',
        'academic_year_id',
        'semester_id',
        'positive_points',
        'negative_points',
        'net_score',
        'record_count',
        'last_incident_at',
    ];

    protected $casts = [
        'positive_points' => 'integer',
        'negative_points' => 'integer',
        'net_score' => 'integer',
        'record_count' => 'integer',
        'last_incident_at' => 'datetime',
    ];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
