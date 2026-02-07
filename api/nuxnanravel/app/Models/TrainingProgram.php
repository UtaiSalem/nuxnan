<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TrainingProgram Model - หลักสูตรอบรม
 */
class TrainingProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'code',
        'name',
        'description',
        'type',
        'provider',
        'duration_hours',
        'start_date',
        'end_date',
        'location',
        'max_participants',
        'cost_per_person',
        'target_positions',
        'prerequisites',
        'is_mandatory',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_hours' => 'integer',
        'max_participants' => 'integer',
        'cost_per_person' => 'decimal:2',
        'target_positions' => 'array',
        'prerequisites' => 'array',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Type constants
    const TYPE_INTERNAL = 'internal';
    const TYPE_EXTERNAL = 'external';
    const TYPE_ONLINE = 'online';
    const TYPE_WORKSHOP = 'workshop';
    const TYPE_CERTIFICATION = 'certification';

    const TYPES = [
        self::TYPE_INTERNAL => 'อบรมภายใน',
        self::TYPE_EXTERNAL => 'อบรมภายนอก',
        self::TYPE_ONLINE => 'อบรมออนไลน์',
        self::TYPE_WORKSHOP => 'เวิร์คช็อป',
        self::TYPE_CERTIFICATION => 'รับรองคุณวุฒิ',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function staffTrainings(): HasMany
    {
        return $this->hasMany(StaffTraining::class);
    }

    // Accessors
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? '';
    }

    public function getEnrolledCountAttribute(): int
    {
        return $this->staffTrainings()->count();
    }

    public function getCompletedCountAttribute(): int
    {
        return $this->staffTrainings()->where('status', 'completed')->count();
    }

    public function getAvailableSlotsAttribute(): ?int
    {
        if (!$this->max_participants) return null;
        return max(0, $this->max_participants - $this->enrolled_count);
    }

    // Methods
    public function hasAvailableSlots(): bool
    {
        if (!$this->max_participants) return true;
        return $this->enrolled_count < $this->max_participants;
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopeOngoing($query)
    {
        return $query->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }
}
