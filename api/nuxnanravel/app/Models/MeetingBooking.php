<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MeetingBooking Model - การจองประชุมผู้ปกครอง-ครู
 */
class MeetingBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'slot_id',
        'parent_id',
        'student_id',
        'booked_time',
        'purpose',
        'status',
        'teacher_notes',
        'parent_notes',
        'confirmed_at',
        'cancelled_at',
        'cancelled_by',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_COMPLETED = 'completed';

    const STATUSES = [
        self::STATUS_PENDING => 'รอยืนยัน',
        self::STATUS_CONFIRMED => 'ยืนยันแล้ว',
        self::STATUS_CANCELLED => 'ยกเลิก',
        self::STATUS_COMPLETED => 'เสร็จสิ้น',
    ];

    // Relationships
    public function slot(): BelongsTo
    {
        return $this->belongsTo(MeetingSlot::class, 'slot_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // Accessors
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getMeetingDatetimeAttribute(): ?Carbon
    {
        if (! $this->slot) {
            return null;
        }

        return Carbon::parse($this->slot->meeting_date->format('Y-m-d').' '.$this->booked_time);
    }

    // Methods
    public function confirm(): void
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    public function cancel(string $cancelledBy): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy,
        ]);
    }

    public function complete(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    // Scopes
    public function scopeBySlot($query, $slotId)
    {
        return $query->where('slot_id', $slotId);
    }

    public function scopeByParent($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereHas('slot', function ($q) {
            $q->where('meeting_date', '>=', now()->toDateString());
        })->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }
}
