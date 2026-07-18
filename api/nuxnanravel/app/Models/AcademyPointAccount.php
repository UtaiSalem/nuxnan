<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademyPointAccount extends Model
{
    protected $fillable = ['academy_id', 'balance', 'reserved_balance', 'platform_earned', 'total_earned', 'total_withdrawn', 'total_distributed', 'minimum_withdrawal', 'commission_rate', 'version'];

    protected $casts = ['commission_rate' => 'decimal:4'];

    protected $appends = ['available_balance'];

    public const MINIMUM_WITHDRAWAL = 24000;

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AcademyPointTransaction::class);
    }

    public function getAvailableBalanceAttribute(): int
    {
        return (int) max(0, $this->balance - ($this->reserved_balance ?? 0));
    }

    public function canReserve(int $amount): bool
    {
        return $this->available_balance >= $amount;
    }

    public function canWithdraw(int $amount): bool
    {
        return $this->canReserve($amount) && $amount >= $this->minimum_withdrawal;
    }

    public function incrementPlatformEarned(int $amount): bool
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Platform earned amount cannot be negative.');
        }

        return $this->increment('platform_earned', $amount) && $this->increment('version');
    }
}
