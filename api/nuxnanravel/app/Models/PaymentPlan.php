<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PaymentPlan Model - แผนการผ่อนชำระ
 */
class PaymentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'description',
        'number_of_installments',
        'installment_percentages',
        'interest_rate',
        'is_active',
    ];

    protected $casts = [
        'number_of_installments' => 'integer',
        'installment_percentages' => 'array',
        'interest_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function studentPaymentPlans(): HasMany
    {
        return $this->hasMany(StudentPaymentPlan::class);
    }

    // Methods
    public function calculateInstallments(float $totalAmount): array
    {
        $installments = [];
        $percentages = $this->installment_percentages ?? [];

        // Add interest if applicable
        $totalWithInterest = $totalAmount * (1 + ($this->interest_rate / 100));

        foreach ($percentages as $index => $percentage) {
            $installments[] = [
                'installment' => $index + 1,
                'percentage' => $percentage,
                'amount' => round($totalWithInterest * ($percentage / 100), 2),
            ];
        }

        return $installments;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }
}
