<?php

namespace App\Models\Learn\Academy;

use App\Models\Academy;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BehaviorRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'student_id',
        'category_id',
        'session_id',
        'type',
        'points',
        'title',
        'description',
        'incident_date',
        'severity',
        'status',
        'evidence',
        'recorded_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'classroom_id',
        'academic_year_id',
        'semester_id',
    ];

    protected $casts = [
        'points' => 'integer',
        'incident_date' => 'date',
        'evidence' => 'array',
        'approved_at' => 'datetime',
    ];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BehaviorCategory::class, 'category_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(BehaviorSession::class, 'session_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function needsApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApplied(): bool
    {
        return in_array($this->status, ['recorded', 'approved']);
    }
}
