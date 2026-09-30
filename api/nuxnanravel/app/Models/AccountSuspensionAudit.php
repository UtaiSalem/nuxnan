<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit record for a single economy (points/wallet) suspension action.
 */
class AccountSuspensionAudit extends Model
{
    protected $fillable = [
        'user_id',
        'user_email',
        'action',
        'points_suspended',
        'wallet_suspended',
        'reason',
        'performed_by',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'points_suspended' => 'boolean',
        'wallet_suspended' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
