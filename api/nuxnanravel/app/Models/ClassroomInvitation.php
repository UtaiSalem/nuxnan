<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ClassroomInvitation Model - คำเชิญเข้าร่วมห้องเรียน
 */
class ClassroomInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'token',
        'invited_email',
        'invited_user_id',
        'role_to_assign',
        'status',
        'invited_by',
        'expires_at',
        'responded_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_ACCEPTED = 'accepted';

    const STATUS_DECLINED = 'declined';

    const STATUS_EXPIRED = 'expired';

    const STATUS_CANCELLED = 'cancelled';

    // ───── Relationships ─────

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // ───── Scopes ─────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('expires_at', '>', now());
    }

    // ───── Helpers ─────

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING && ! $this->isExpired();
    }

    public function markAccepted(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);
    }

    public function markDeclined(): bool
    {
        return $this->update([
            'status' => self::STATUS_DECLINED,
            'responded_at' => now(),
        ]);
    }
}
