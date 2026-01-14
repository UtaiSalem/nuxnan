<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReward extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_rewards';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'reward_id',
        'points_spent',
        'status',
        'redeemed_at',
        'expires_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'points_spent' => 'integer',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the user reward.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reward associated with the user reward.
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    /**
     * Scope a query to only include pending rewards.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include claimed rewards.
     */
    public function scopeClaimed($query)
    {
        return $query->where('status', 'claimed');
    }

    /**
     * Scope a query to only include expired rewards.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope a query to only include cancelled rewards.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include active rewards (not expired or cancelled).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'claimed']);
    }

    /**
     * Check if the reward is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the reward is claimed.
     */
    public function isClaimed(): bool
    {
        return $this->status === 'claimed';
    }

    /**
     * Check if the reward is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Check if the reward is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get the status label attribute.
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
     * Get the formatted points spent attribute.
     */
    public function getFormattedPointsSpentAttribute(): string
    {
        return number_format($this->points_spent) . ' แต้ม';
    }

    /**
     * Mark the reward as claimed.
     */
    public function markAsClaimed(): bool
    {
        return $this->update(['status' => 'claimed']);
    }

    /**
     * Mark the reward as expired.
     */
    public function markAsExpired(): bool
    {
        return $this->update(['status' => 'expired']);
    }

    /**
     * Mark the reward as cancelled.
     */
    public function markAsCancelled(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }
}
