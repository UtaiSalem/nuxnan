<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminUserDeletionAudit extends Model
{
    protected $fillable = [
        'deleted_user_id',
        'deleted_user_email',
        'deleted_by',
        'mode',
        'reason',
        'user_snapshot',
        'impact_snapshot',
    ];

    protected $casts = [
        'user_snapshot' => 'array',
        'impact_snapshot' => 'array',
    ];

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
