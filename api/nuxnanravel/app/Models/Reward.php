<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'points_cost',
        'image_url',
        'stock',
        'max_redemptions_per_user',
        'is_active',
        'available_from',
        'available_until',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'points_cost' => 'integer',
        'stock' => 'integer',
        'max_redemptions_per_user' => 'integer',
        'is_active' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    /**
     * Get user rewards for this reward
     */
    public function userRewards(): HasMany
    {
        return $this->hasMany(UserReward::class);
    }

    /**
     * Scope for active rewards
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('available_from')
                    ->orWhere('available_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('available_until')
                    ->orWhere('available_until', '>', now());
            });
    }

    /**
     * Scope for type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for available stock
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('stock')
                ->orWhere('stock', '>', 0);
        });
    }

    /**
     * Check if reward is currently available
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->available_from && $this->available_from > $now) {
            return false;
        }

        if ($this->available_until && $this->available_until <= $now) {
            return false;
        }

        if ($this->stock !== null && $this->stock <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can redeem this reward
     */
    public function canRedeem(int $userPoints, int $userRedemptionsCount = 0): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        if ($userPoints < $this->points_cost) {
            return false;
        }

        if ($this->max_redemptions_per_user !== null && $userRedemptionsCount >= $this->max_redemptions_per_user) {
            return false;
        }

        return true;
    }

    /**
     * Get type label in Thai
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'points' => 'แต้ม',
            'wallet' => 'เงิน Wallet',
            'badge' => 'Badge',
            'feature' => 'ฟีเจอร์',
            'discount' => 'ส่วนลด',
            default => $this->type,
        };
    }

    /**
     * Get formatted points cost
     */
    public function getFormattedPointsCostAttribute(): string
    {
        return number_format($this->points_cost) . ' แต้ม';
    }

    /**
     * Get formatted value
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->type === 'wallet') {
            return number_format($this->value, 2) . ' บาท';
        }

        return number_format($this->value) . ' ' . $this->getTypeLabelAttribute();
    }
}
