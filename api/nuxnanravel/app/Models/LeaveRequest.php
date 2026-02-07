<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

/**
 * LeaveRequest Model - คำขอลา
 */
class LeaveRequest extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'staff_profile_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'leave_period',
        'reason',
        'document_path',
        'contact_during_leave',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'substitute_staff_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_PENDING => 'รออนุมัติ',
        self::STATUS_APPROVED => 'อนุมัติแล้ว',
        self::STATUS_REJECTED => 'ไม่อนุมัติ',
        self::STATUS_CANCELLED => 'ยกเลิก',
    ];

    // Leave period constants
    const PERIOD_FULL_DAY = 'full_day';
    const PERIOD_MORNING = 'morning';
    const PERIOD_AFTERNOON = 'afternoon';

    // Relationships
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function substituteStaff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class, 'substitute_staff_id');
    }

    // Accessors
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getDurationTextAttribute(): string
    {
        if ($this->start_date->equalTo($this->end_date)) {
            return $this->start_date->format('d/m/Y');
        }
        return $this->start_date->format('d/m/Y') . ' - ' . $this->end_date->format('d/m/Y');
    }

    // Methods
    public function approve(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function reject(int $userId, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function cancel(): void
    {
        if ($this->status === self::STATUS_PENDING) {
            $this->update(['status' => self::STATUS_CANCELLED]);
        }
    }

    public function calculateTotalDays(): float
    {
        $days = $this->start_date->diffInDays($this->end_date) + 1;
        
        if ($this->leave_period !== self::PERIOD_FULL_DAY) {
            $days = $days * 0.5;
        }
        
        return $days;
    }

    // Scopes
    public function scopeByStaff($query, $staffProfileId)
    {
        return $query->where('staff_profile_id', $staffProfileId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    public function scopeByYear($query, $year)
    {
        return $query->whereYear('start_date', $year);
    }
}
