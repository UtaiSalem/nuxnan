<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'source_type',
        'source_id',
        'description',
        'metadata',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for transaction type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope for source type
     */
    public function scopeSource($query, string $sourceType)
    {
        return $query->where('source_type', $sourceType);
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
        return number_format($this->amount, 2) . ' แต้ม';
    }

    /**
     * Get transaction type label in Thai
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->transaction_type) {
            'earn' => 'การได้แต้ม',
            'spend' => 'การใช้แต้ม',
            'refund' => 'การคืนแต้ม',
            'transfer' => 'การโอนแต้ม',
            'transfer_in' => 'รับโอนแต้ม',
            'transfer_out' => 'โอนแต้มออก',
            'admin_adjust' => 'การปรับจาก Admin',
            'conversion' => 'การแปลงแต้ม',
            default => $this->transaction_type,
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'รอดำเนินการ',
            'completed' => 'เสร็จสิ้น',
            'failed' => 'ล้มเหลว',
            'cancelled' => 'ยกเลิก',
            default => $this->status,
        };
    }
}
