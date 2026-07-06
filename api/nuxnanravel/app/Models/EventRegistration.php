<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EventRegistration Model - การลงทะเบียนกิจกรรม
 */
class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'guests',
        'guest_names',
        'notes',
        'payment_required',
        'payment_completed',
        'payment_id',
        'confirmed_at',
        'cancelled_at',
        'attended_at',
    ];

    protected $casts = [
        'guest_names' => 'array',
        'payment_required' => 'boolean',
        'payment_completed' => 'boolean',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'attended_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_ATTENDED = 'attended';

    const STATUS_NO_SHOW = 'no_show';

    const STATUSES = [
        self::STATUS_PENDING => 'รอยืนยัน',
        self::STATUS_CONFIRMED => 'ยืนยันแล้ว',
        self::STATUS_CANCELLED => 'ยกเลิก',
        self::STATUS_ATTENDED => 'เข้าร่วมแล้ว',
        self::STATUS_NO_SHOW => 'ไม่มา',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(SchoolEvent::class, 'event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // Accessors
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getTotalParticipantsAttribute(): int
    {
        return 1 + $this->guests;
    }

    // Methods
    public function confirm(): void
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    public function markAsAttended(): void
    {
        $this->update([
            'status' => self::STATUS_ATTENDED,
            'attended_at' => now(),
        ]);
    }

    public function markAsNoShow(): void
    {
        $this->update(['status' => self::STATUS_NO_SHOW]);
    }

    // Scopes
    public function scopeByEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
