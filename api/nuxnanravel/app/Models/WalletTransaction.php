<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $appends = ['type_label', 'has_payout_proof'];

    protected $hidden = [
        'payout_proof_path',
        'destination_snapshot',
        'idempotency_key',
    ];

    protected $fillable = [
        'user_id',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'currency',
        'description',
        'metadata',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'admin_note',
        'payment_reference',
        'processed_at',
        'failed_at',
        'idempotency_key',
        'version',
        'fee',
        'net_amount',
        'destination_type',
        'destination_snapshot',
        'reference_number',
        'payout_proof_path',
        'payout_proof_original_name',
        'payout_proof_mime',
        'payout_proof_size',
        'payout_proof_uploaded_by',
        'payout_proof_uploaded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'payout_proof_uploaded_at' => 'datetime',
    ];

    /**
     * Get the user that owns this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = [
            'pending' => ['under_review', 'cancelled'],
            'under_review' => ['approved', 'rejected'],
            'approved' => ['processing', 'failed'],
            'processing' => ['paid', 'failed'],
            'paid' => [],
            'completed' => [],
            'rejected' => [],
            'failed' => [],
            'cancelled' => [],
        ];

        return in_array($newStatus, $allowed[$this->status] ?? [], true);
    }

    /**
     * Scope for transaction type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope for status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope for reference number
     */
    public function scopeReference($query, string $reference)
    {
        return $query->where('reference_number', $reference);
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2).' บาท';
    }

    /**
     * Get transaction type label in Thai
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->transaction_type) {
            'deposit' => 'การฝากเงิน',
            'withdraw' => 'การถอนเงิน',
            'transfer' => 'การโอนเงิน',
            'conversion' => 'การแปลงแต้ม',
            'admin_adjust' => 'การปรับจาก Admin',
            'reward' => 'การรับรางวัล',
            'purchase' => 'การซื้อสินค้า',
            'course_income' => 'รายได้รายวิชา',
            'refund' => 'การคืนเงิน',
            'opening_balance' => 'ยอดยกมา',
            default => $this->transaction_type,
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'รอดำเนินการ',
            'completed' => 'เสร็จสิ้น',
            'failed' => 'ล้มเหลว',
            'cancelled' => 'ยกเลิก',
            default => $this->status,
        };
    }

    /**
     * Get formatted currency amount
     */
    public function getFormattedCurrencyAttribute(): string
    {
        return $this->currency.' '.number_format($this->amount, 2);
    }

    /**
     * Check if the transaction has a payout proof
     */
    public function getHasPayoutProofAttribute(): bool
    {
        return ! empty($this->payout_proof_path);
    }
}
