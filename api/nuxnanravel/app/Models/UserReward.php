<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reward_id',
        'points_spent',
        'status',
        'redeemed_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'points_spent' => 'integer',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get user that owns this user reward.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get reward for this user reward.
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    /**
     * Scope for claimed rewards
     */
    public function scopeClaimed($query)
    {
        return $query->where('status', 'claimed');
    }

    /**
     * Scope for pending rewards
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for expired rewards
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope for cancelled rewards
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Check if reward is claimed
     */
    public function isClaimed(): bool
    {
        return $this->status === 'claimed';
    }

    /**
     * Check if reward is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if reward is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->expires_at && $this->expires_at <= now());
    }

    /**
     * Check if reward is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'รอดำเนินการ',
            'claimed' => 'ได้รับแล้ว',
            'expired' => 'หมดอายุ',
            'cancelled' => 'ยกเลิก',
            default => $this->status,
        };
    }

    /**
     * Get formatted points spent
     */
    public function getFormattedPointsSpentAttribute(): string
    {
        return number_format($this->points_spent) . ' แต้ม';
    }

    /**
     * Get formatted redeemed date
     */
    public function getFormattedRedeemedAtAttribute(): string
    {
        if (!$this->redeemed_at) {
            return '-';
        }

        return $this->redeemed_at->locale('th')->translatedFormat('j F Y H:i');
    }

    /**
     * Get formatted expiry date
     */
    public function getFormattedExpiresAtAttribute(): string
    {
        if (!$this->expires_at) {
            return '-';
        }

        return $this->expires_at->locale('th')->translatedFormat('j F Y H:i');
    }
}
