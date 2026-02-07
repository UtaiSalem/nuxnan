<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ReportSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'report_id',
        'user_id',
        'frequency',
        'day_of_week',
        'day_of_month',
        'scheduled_time',
        'export_format',
        'recipients',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    // Frequencies
    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_QUARTERLY = 'quarterly';
    const FREQUENCY_YEARLY = 'yearly';

    const FREQUENCIES = [
        self::FREQUENCY_DAILY,
        self::FREQUENCY_WEEKLY,
        self::FREQUENCY_MONTHLY,
        self::FREQUENCY_QUARTERLY,
        self::FREQUENCY_YEARLY,
    ];

    // Export Formats
    const FORMAT_PDF = 'pdf';
    const FORMAT_EXCEL = 'excel';
    const FORMAT_CSV = 'csv';

    const FORMATS = [
        self::FORMAT_PDF,
        self::FORMAT_EXCEL,
        self::FORMAT_CSV,
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(SavedReport::class, 'report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('next_run_at', '<=', now());
    }

    // Methods
    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function markAsRun(): void
    {
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }

    public function calculateNextRun(): Carbon
    {
        $now = Carbon::now();
        $time = Carbon::parse($this->scheduled_time);

        switch ($this->frequency) {
            case self::FREQUENCY_DAILY:
                $next = $now->copy()->setTimeFrom($time);
                if ($next->lte($now)) {
                    $next->addDay();
                }
                break;

            case self::FREQUENCY_WEEKLY:
                $next = $now->copy()->next((int) $this->day_of_week)->setTimeFrom($time);
                break;

            case self::FREQUENCY_MONTHLY:
                $next = $now->copy()->day($this->day_of_month)->setTimeFrom($time);
                if ($next->lte($now)) {
                    $next->addMonth();
                }
                break;

            case self::FREQUENCY_QUARTERLY:
                $next = $now->copy()->addQuarter()->startOfQuarter()->day($this->day_of_month ?? 1)->setTimeFrom($time);
                break;

            case self::FREQUENCY_YEARLY:
                $next = $now->copy()->addYear()->startOfYear()->day($this->day_of_month ?? 1)->setTimeFrom($time);
                break;

            default:
                $next = $now->copy()->addDay()->setTimeFrom($time);
        }

        return $next;
    }
}
