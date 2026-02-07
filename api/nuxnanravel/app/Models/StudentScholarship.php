<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StudentScholarship Model - ทุนการศึกษาของนักเรียน
 */
class StudentScholarship extends Model
{
    use HasFactory;

    protected $fillable = [
        'scholarship_id',
        'user_id',
        'academic_year_id',
        'awarded_date',
        'start_date',
        'end_date',
        'status',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'awarded_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_PENDING => 'รออนุมัติ',
        self::STATUS_ACTIVE => 'ใช้งานอยู่',
        self::STATUS_SUSPENDED => 'ระงับชั่วคราว',
        self::STATUS_EXPIRED => 'หมดอายุ',
        self::STATUS_CANCELLED => 'ยกเลิก',
    ];

    // Relationships
    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
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

    // Methods
    public function approve(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function suspend(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_SUSPENDED,
            'notes' => $reason,
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeByStudent($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }
}
