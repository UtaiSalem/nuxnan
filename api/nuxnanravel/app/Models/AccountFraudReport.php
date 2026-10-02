<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member-submitted fraud report against another account. Reviewed by admins,
 * who may freeze the reported account's economy (points/wallet) from the queue.
 */
class AccountFraudReport extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWING = 'reviewing';

    public const STATUS_ACTION_TAKEN = 'action_taken';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REVIEWING,
        self::STATUS_ACTION_TAKEN,
        self::STATUS_DISMISSED,
    ];

    /**
     * Statuses that close a report (an admin has finished with it).
     */
    public const RESOLVED_STATUSES = [
        self::STATUS_ACTION_TAKEN,
        self::STATUS_DISMISSED,
    ];

    public const CATEGORIES = [
        'scam',
        'phishing',
        'point_fraud',
        'money_fraud',
        'fake_account',
        'other',
    ];

    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'category',
        'description',
        'related_transaction_type',
        'related_transaction_id',
        'evidence_note',
        'status',
        'handled_by',
        'admin_note',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Whether a given status closes the report.
     */
    public static function isResolvedStatus(string $status): bool
    {
        return in_array($status, self::RESOLVED_STATUSES, true);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * Resolve the referenced transaction (points or wallet), if any. Returns
     * null when no transaction was attached or it no longer exists.
     */
    public function relatedTransaction(): ?Model
    {
        if (! $this->related_transaction_id || ! $this->related_transaction_type) {
            return null;
        }

        return match ($this->related_transaction_type) {
            'points' => PointsTransaction::find($this->related_transaction_id),
            'wallet' => WalletTransaction::find($this->related_transaction_id),
            default => null,
        };
    }
}
