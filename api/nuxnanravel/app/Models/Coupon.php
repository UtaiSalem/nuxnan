<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coupon_code',
        'coupon_type',
        'amount',
        'status',
        'description',
        'expires_at',
        'redeemed_at',
        'redeemed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'status_label',
        'type_label',
        'is_expired',
        'can_redeem',
        'qr_code_path',
        'qr_code_url',
    ];

    /**
     * Get the user that owns the coupon.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who redeemed the coupon.
     */
    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    /**
     * Get the redemptions for the coupon.
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /**
     * Scope for active coupons.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for points coupons.
     */
    public function scopePoints($query)
    {
        return $query->where('coupon_type', 'points');
    }

    /**
     * Scope for wallet coupons.
     */
    public function scopeWallet($query)
    {
        return $query->where('coupon_type', 'wallet');
    }

    /**
     * Scope for not expired coupons.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', Carbon::now());
        });
    }

    /**
     * Check if coupon is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get is_expired attribute.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->isExpired();
    }

    /**
     * Check if coupon can be redeemed.
     */
    public function canRedeem(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    /**
     * Get can_redeem attribute.
     */
    public function getCanRedeemAttribute(): bool
    {
        return $this->canRedeem();
    }

    /**
     * Get status label in Thai.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'ใช้งานได้',
            'redeemed' => 'ใช้แล้ว',
            'expired' => 'หมดอายุ',
            'cancelled' => 'ยกเลิก',
            default => $this->status,
        };
    }

    /**
     * Get type label in Thai.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->coupon_type) {
            'points' => 'คูปองแต้ม',
            'wallet' => 'คูปองเงิน',
            default => $this->coupon_type,
        };
    }

    /**
     * Get QR code path (derived from coupon_code).
     */
    public function getQrCodePathAttribute(): string
    {
        return 'qr-codes/' . $this->coupon_code . '.svg';
    }

    /**
     * Get QR code URL.
     */
    public function getQrCodeUrlAttribute(): string
    {
        return url('storage/' . $this->qr_code_path);
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        $unit = $this->coupon_type === 'points' ? 'แต้ม' : 'บาท';
        return number_format($this->amount, $this->coupon_type === 'wallet' ? 2 : 0) . ' ' . $unit;
    }

    /**
     * Mark coupon as redeemed.
     */
    public function markAsRedeemed(User $user): bool
    {
        return $this->update([
            'status' => 'redeemed',
            'redeemed_at' => Carbon::now(),
            'redeemed_by' => $user->id,
        ]);
    }

    /**
     * Cancel the coupon.
     */
    public function cancel(): bool
    {
        return $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Mark expired coupons.
     */
    public function markExpired(): bool
    {
        if (!$this->isExpired()) {
            return false;
        }
        return $this->update([
            'status' => 'expired',
        ]);
    }
}
