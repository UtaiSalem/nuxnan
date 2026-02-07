<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * StudentPaymentPlan Model - แผนผ่อนชำระของนักเรียน
 */
class StudentPaymentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'tuition_fee_id',
        'payment_plan_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'next_due_date',
        'status',
        'installments_data',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'next_due_date' => 'date',
        'installments_data' => 'array',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_ACTIVE => 'กำลังผ่อนชำระ',
        self::STATUS_COMPLETED => 'ชำระครบแล้ว',
        self::STATUS_OVERDUE => 'ค้างชำระ',
        self::STATUS_CANCELLED => 'ยกเลิก',
    ];

    // Relationships
    public function tuitionFee(): BelongsTo
    {
        return $this->belongsTo(TuitionFee::class);
    }

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    // Accessors
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getPaidInstallmentsAttribute(): int
    {
        $data = $this->installments_data ?? [];
        return collect($data)->where('paid', true)->count();
    }

    public function getTotalInstallmentsAttribute(): int
    {
        return count($this->installments_data ?? []);
    }

    // Methods
    public function recordPayment(float $amount): void
    {
        $this->paid_amount += $amount;
        $this->remaining_amount = $this->total_amount - $this->paid_amount;

        // Update installments data
        $installments = $this->installments_data ?? [];
        foreach ($installments as &$installment) {
            if (!($installment['paid'] ?? false) && $amount >= $installment['amount']) {
                $installment['paid'] = true;
                $installment['paid_at'] = now()->toDateTimeString();
                $amount -= $installment['amount'];
            }
        }
        $this->installments_data = $installments;

        // Update status
        if ($this->remaining_amount <= 0) {
            $this->status = self::STATUS_COMPLETED;
        }

        // Update next due date
        $this->updateNextDueDate();

        $this->save();
    }

    protected function updateNextDueDate(): void
    {
        $installments = $this->installments_data ?? [];
        foreach ($installments as $installment) {
            if (!($installment['paid'] ?? false) && isset($installment['due_date'])) {
                $this->next_due_date = $installment['due_date'];
                return;
            }
        }
        $this->next_due_date = null;
    }

    public function checkOverdue(): void
    {
        if ($this->status === self::STATUS_ACTIVE && 
            $this->next_due_date && 
            $this->next_due_date < now()->toDateString()) {
            $this->update(['status' => self::STATUS_OVERDUE]);
        }
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }
}
