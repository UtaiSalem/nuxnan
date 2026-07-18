<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoursePointWithdrawalRequest extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWING = 'reviewing';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const TERMINAL_STATUSES = [self::STATUS_PAID, self::STATUS_REJECTED, self::STATUS_CANCELLED];

    protected $fillable = ['course_id', 'course_point_account_id', 'requested_by', 'amount', 'purpose', 'status', 'reviewed_by', 'reviewed_at', 'approved_by', 'approved_at', 'paid_by', 'paid_at', 'payout_proof_path', 'payout_proof_original_name', 'payout_proof_mime', 'payout_proof_size', 'payment_reference', 'rejection_reason', 'admin_note', 'metadata', 'version', 'idempotency_key', 'course_point_transaction_id'];

    protected $casts = ['amount' => 'integer', 'payout_proof_size' => 'integer', 'metadata' => 'array', 'reviewed_at' => 'datetime', 'approved_at' => 'datetime', 'paid_at' => 'datetime'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CoursePointAccount::class, 'course_point_account_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(CoursePointTransaction::class, 'course_point_transaction_id');
    }

    public function canTransitionTo(string $to): bool
    {
        return match ($this->status) {
            self::STATUS_PENDING => in_array($to, [self::STATUS_REVIEWING, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED], true),
            self::STATUS_REVIEWING => in_array($to, [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED], true),
            self::STATUS_APPROVED => $to === self::STATUS_PAID,
            default => false,
        };
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAwaitingApproval(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_REVIEWING]);
    }
}
