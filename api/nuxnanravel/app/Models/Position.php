<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Position Model - ตำแหน่ง
 */
class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'department_id',
        'code',
        'name',
        'level',
        'description',
        'responsibilities',
        'min_salary',
        'max_salary',
        'is_teaching_position',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'responsibilities' => 'array',
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'is_teaching_position' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function staffProfiles(): HasMany
    {
        return $this->hasMany(StaffProfile::class);
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

    public function scopeTeaching($query)
    {
        return $query->where('is_teaching_position', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}
