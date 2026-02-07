<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FeeStructure Model - โครงสร้างค่าเล่าเรียน
 */
class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'academic_year_id',
        'name',
        'description',
        'grade_levels',
        'total_amount',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'grade_levels' => 'array',
        'total_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function feeItems(): HasMany
    {
        return $this->hasMany(FeeItem::class)->orderBy('display_order');
    }

    public function tuitionFees(): HasMany
    {
        return $this->hasMany(TuitionFee::class);
    }

    // Calculate total from items
    public function calculateTotal(): float
    {
        return $this->feeItems()->sum('amount');
    }

    public function updateTotal(): void
    {
        $this->update(['total_amount' => $this->calculateTotal()]);
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

    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeForGradeLevel($query, $gradeLevel)
    {
        return $query->whereJsonContains('grade_levels', $gradeLevel);
    }
}
