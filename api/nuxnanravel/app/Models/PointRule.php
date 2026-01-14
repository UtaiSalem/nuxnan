<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_key',
        'rule_name',
        'description',
        'action_type',
        'source_type',
        'base_amount',
        'multiplier',
        'max_daily_earnings',
        'max_monthly_earnings',
        'cooldown_minutes',
        'is_active',
        'effective_date',
        'expiry_date',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'multiplier' => 'decimal:2',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('effective_date')
                    ->orWhere('effective_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            });
    }

    /**
     * Scope for action type
     */
    public function scopeActionType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope for source type
     */
    public function scopeSourceType($query, string $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    /**
     * Check if rule is currently active
     */
    public function isActiveNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->effective_date && $this->effective_date > $now) {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date <= $now) {
            return false;
        }

        return true;
    }

    /**
     * Calculate final amount with multiplier
     */
    public function calculateAmount(int $baseCount = 1): float
    {
        return $this->base_amount * $this->multiplier * $baseCount;
    }

    /**
     * Get action type label in Thai
     */
    public function getActionTypeLabelAttribute(): string
    {
        return match($this->action_type) {
            'earn' => 'การได้แต้ม',
            'spend' => 'การใช้แต้ม',
            default => $this->action_type,
        };
    }
}
