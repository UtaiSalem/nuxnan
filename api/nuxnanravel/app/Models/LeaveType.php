<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LeaveType Model - ประเภทการลา
 */
class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'code',
        'name',
        'description',
        'max_days_per_year',
        'is_paid',
        'requires_approval',
        'requires_document',
        'advance_notice_days',
        'applicable_to',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'max_days_per_year' => 'integer',
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
        'requires_document' => 'boolean',
        'advance_notice_days' => 'integer',
        'applicable_to' => 'array',
        'is_active' => 'boolean',
    ];

    // Common leave type codes
    const CODE_ANNUAL = 'ANNUAL';

    const CODE_SICK = 'SICK';

    const CODE_PERSONAL = 'PERSONAL';

    const CODE_MATERNITY = 'MATERNITY';

    const CODE_PATERNITY = 'PATERNITY';

    const CODE_ORDINATION = 'ORDINATION';

    const CODE_MILITARY = 'MILITARY';

    const CODE_UNPAID = 'UNPAID';

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
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

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}
