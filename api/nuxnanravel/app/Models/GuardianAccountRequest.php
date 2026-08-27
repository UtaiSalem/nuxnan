<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GuardianAccountRequest represents a request to link an existing or new guardian record
 * to an actual User account.
 *
 * The `direction` field indicates who must accept the request:
 * - 'guardian': The account owner (parent) must accept.
 * - 'student': The student must accept.
 */
class GuardianAccountRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_CANCELLED = 'cancelled';

    public const DIRECTION_GUARDIAN = 'guardian';

    public const DIRECTION_STUDENT = 'student';

    public const ROLE_STUDENT = 'student';

    public const ROLE_GUARDIAN = 'guardian';

    public const ROLE_HOMEROOM = 'homeroom';

    public const ROLE_STAFF = 'staff';

    public const ROLE_OWNER = 'owner';

    protected $fillable = [
        'academy_id',
        'student_id',
        'guardian_id',
        'user_id',
        'direction',
        'initiated_by_user_id',
        'initiated_by_role',
        'status',
        'responded_by_user_id',
        'responded_at',
        'decline_reason',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by_user_id');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
