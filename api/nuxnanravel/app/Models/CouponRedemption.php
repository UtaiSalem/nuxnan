<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'user_id',
        'redeemed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the coupon that was redeemed.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the user who redeemed the coupon.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('redeemed_at', [$from, $to]);
    }

    /**
     * Scope for specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific coupon.
     */
    public function scopeForCoupon($query, int $couponId)
    {
        return $query->where('coupon_id', $couponId);
    }

    /**
     * Get formatted redemption time.
     */
    public function getFormattedRedeemedAtAttribute(): string
    {
        return $this->redeemed_at ? $this->redeemed_at->format('d/m/Y H:i:s') : '-';
    }
}
