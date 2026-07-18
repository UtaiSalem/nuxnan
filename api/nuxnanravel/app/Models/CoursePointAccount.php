<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoursePointAccount extends Model
{
    protected $fillable = [
        'course_id', 'balance', 'total_earned', 'total_withdrawn', 'total_distributed',
        'reserved_balance', 'commission_rate', 'minimum_withdrawal', 'version',
    ];

    protected $casts = ['commission_rate' => 'decimal:4'];

    protected $appends = ['available_balance'];

    const MINIMUM_WITHDRAWAL = 24000; // 20 THB

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CoursePointTransaction::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(CoursePointCampaign::class);
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
        return $this->available_balance >= $amount
            && $amount >= $this->minimum_withdrawal;
    }
}
