<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueSharePolicy extends Model
{
    public const SCOPE_PLATFORM = 'platform';

    public const SCOPE_ACADEMY = 'academy';

    public const SCOPE_COURSE = 'course';

    public const SCOPE_CAMPAIGN = 'campaign';

    protected $fillable = ['scope_type', 'scope_id', 'student_pct', 'course_pct', 'academy_pct', 'platform_pct', 'effective_from', 'effective_to', 'version', 'notes', 'created_by'];

    protected $casts = ['student_pct' => 'decimal:2', 'course_pct' => 'decimal:2', 'academy_pct' => 'decimal:2', 'platform_pct' => 'decimal:2', 'effective_from' => 'datetime', 'effective_to' => 'datetime'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query, ?Carbon $at = null): void
    {
        $at ??= now();
        $query->where('effective_from', '<=', $at)->where(function (Builder $q) use ($at) {
            $q->whereNull('effective_to')->orWhere('effective_to', '>', $at);
        });
    }

    public function sumsTo100(): bool
    {
        return round((float) $this->student_pct + (float) $this->course_pct + (float) $this->academy_pct + (float) $this->platform_pct, 2) === 100.00;
    }
}
