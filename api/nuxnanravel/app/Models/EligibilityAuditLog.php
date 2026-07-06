<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EligibilityAuditLog extends Model
{
    protected $fillable = [
        'course_member_id',
        'course_id',
        'old_status',
        'new_status',
        'change_type',
        'changed_by',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    const TYPE_AUTO_CALC = 'auto_calc';

    const TYPE_POINTS_UNLOCK = 'points_unlock';

    const TYPE_READING_UNLOCK = 'reading_unlock';

    const TYPE_ADMIN_UNLOCK = 'admin_unlock';

    const TYPE_APPEAL_UNLOCK = 'appeal_unlock';

    const TYPE_SELF_UNLOCK = 'self_unlock';

    const TYPE_REMEDIATION_UNLOCK = 'remediation_unlock';

    const TYPE_ADMIN_REVOKE = 'admin_revoke';

    const TYPE_EXPIRATION = 'expiration';

    public function courseMember(): BelongsTo
    {
        return $this->belongsTo(CourseMember::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public static function log(
        CourseMember $member,
        string $newStatus,
        string $changeType,
        ?int $changedBy = null,
        ?string $reason = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'course_member_id' => $member->id,
            'course_id' => $member->course_id,
            'old_status' => $member->eligibility_status,
            'new_status' => $newStatus,
            'change_type' => $changeType,
            'changed_by' => $changedBy,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }
}
