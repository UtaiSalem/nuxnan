<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AcademicYear Model - ปีการศึกษา
 */
class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class)->orderBy('semester_number');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function annualTranscripts(): HasMany
    {
        return $this->hasMany(AnnualTranscript::class);
    }

    // Scopes
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    // Methods
    public function setAsCurrent(): void
    {
        // Remove current status from other years in same academy
        self::where('academy_id', $this->academy_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);

        $this->update(['is_current' => true]);
    }

    public function getCurrentSemester()
    {
        return $this->semesters()->where('is_current', true)->first();
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return "ปีการศึกษา {$this->name}";
    }

    public function getDurationAttribute(): string
    {
        return $this->start_date->format('d/m/Y') . ' - ' . $this->end_date->format('d/m/Y');
    }
}
