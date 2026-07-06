<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffTraining Model - การอบรมบุคลากร
 */
class StaffTraining extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_profile_id',
        'training_program_id',
        'enrollment_date',
        'status',
        'completion_date',
        'score',
        'certificate_number',
        'certificate_expiry',
        'feedback',
        'notes',
        'approved_by',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'completion_date' => 'date',
        'score' => 'decimal:2',
        'certificate_expiry' => 'date',
    ];

    // Status constants
    const STATUS_ENROLLED = 'enrolled';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_FAILED = 'failed';

    const STATUSES = [
        self::STATUS_ENROLLED => 'ลงทะเบียนแล้ว',
        self::STATUS_IN_PROGRESS => 'กำลังอบรม',
        self::STATUS_COMPLETED => 'สำเร็จ',
        self::STATUS_CANCELLED => 'ยกเลิก',
        self::STATUS_FAILED => 'ไม่ผ่าน',
    ];

    // Relationships
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessors
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getIsCertificateValidAttribute(): bool
    {
        if (! $this->certificate_number) {
            return false;
        }
        if (! $this->certificate_expiry) {
            return true;
        }

        return $this->certificate_expiry >= now();
    }

    // Methods
    public function start(): void
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    public function complete(?float $score = null, ?string $certificateNumber = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completion_date' => now(),
            'score' => $score,
            'certificate_number' => $certificateNumber,
        ]);
    }

    public function fail(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completion_date' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    // Scopes
    public function scopeByStaff($query, $staffProfileId)
    {
        return $query->where('staff_profile_id', $staffProfileId);
    }

    public function scopeByProgram($query, $trainingProgramId)
    {
        return $query->where('training_program_id', $trainingProgramId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeWithValidCertificate($query)
    {
        return $query->whereNotNull('certificate_number')
            ->where(function ($q) {
                $q->whereNull('certificate_expiry')
                    ->orWhere('certificate_expiry', '>=', now());
            });
    }
}
