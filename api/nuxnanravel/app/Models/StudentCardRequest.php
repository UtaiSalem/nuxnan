<?php

namespace App\Models;

use App\Enums\StudentCardRequestOrigin;
use App\Enums\StudentCardRequestStatus;
use App\Enums\StudentCardRequestType;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCardRequest extends Model
{
    use Auditable;

    protected $guarded = ['id', 'is_open_flag'];

    protected $casts = [
        'request_type' => StudentCardRequestType::class,
        'status' => StudentCardRequestStatus::class,
        'origin' => StudentCardRequestOrigin::class,
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function existingCard(): BelongsTo
    {
        return $this->belongsTo(StudentCard::class, 'existing_card_id');
    }

    public function resultCard(): BelongsTo
    {
        return $this->belongsTo(StudentCard::class, 'result_card_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function getAuditModule(): ?string
    {
        return 'student_card_request';
    }
}
