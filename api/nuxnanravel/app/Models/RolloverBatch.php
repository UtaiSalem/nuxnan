<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RolloverBatch extends Model
{
    use Auditable;

    protected $table = 'rollover_batches';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'academy_id',
        'from_academic_year_id',
        'to_academic_year_id',
        'status',
        'plan_summary',
        'totals',
        'notes',
        'committed_at',
        'committed_by_user_id',
        'undo_closed_at',
        'undone_at',
        'undone_by_user_id',
    ];

    protected $casts = [
        'plan_summary' => 'array',
        'totals' => 'array',
        'committed_at' => 'datetime',
        'undo_closed_at' => 'datetime',
        'undone_at' => 'datetime',
    ];

    public function isUndoable(): bool
    {
        return $this->status === 'committed'
            && $this->undo_closed_at === null
            && $this->committed_at
            && $this->committed_at->copy()->addDay()->isFuture();
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function fromAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'from_academic_year_id');
    }

    public function toAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'to_academic_year_id');
    }

    public function committedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'committed_by_user_id');
    }

    public function undoneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'undone_by_user_id');
    }

    public function classroomStudents(): HasMany
    {
        return $this->hasMany(ClassroomStudent::class, 'rollover_batch_id');
    }

    /**
     * Get the audit module name for this model.
     */
    public function getAuditModule(): ?string
    {
        return 'enrollment';
    }

    /**
     * Get attributes that should be hidden from audit logs.
     */
    public function getAuditHiddenAttributes(): array
    {
        return [
            'plan_summary',
        ];
    }
}
