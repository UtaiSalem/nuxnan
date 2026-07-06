<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Scholarship Model - ทุนการศึกษา
 */
class Scholarship extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_amount',
        'applicable_fee_types',
        'start_date',
        'end_date',
        'max_recipients',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'applicable_fee_types' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'max_recipients' => 'integer',
        'is_active' => 'boolean',
    ];

    // Discount types
    const TYPE_PERCENTAGE = 'percentage';

    const TYPE_FIXED = 'fixed';

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function studentScholarships(): HasMany
    {
        return $this->hasMany(StudentScholarship::class);
    }

    // Methods
    public function calculateDiscount(float $amount): float
    {
        if ($this->discount_type === self::TYPE_PERCENTAGE) {
            $discount = $amount * ($this->discount_value / 100);
            if ($this->max_amount && $discount > $this->max_amount) {
                return $this->max_amount;
            }

            return $discount;
        }

        return min($this->discount_value, $amount);
    }

    public function getActiveRecipientsCount(): int
    {
        return $this->studentScholarships()
            ->where('status', 'active')
            ->count();
    }

    public function hasAvailableSlots(): bool
    {
        if (! $this->max_recipients) {
            return true;
        }

        return $this->getActiveRecipientsCount() < $this->max_recipients;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }
}
