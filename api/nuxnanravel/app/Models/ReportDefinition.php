<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'code',
        'name',
        'description',
        'category',
        'report_type',
        'data_source',
        'columns',
        'filters',
        'grouping',
        'sorting',
        'chart_config',
        'is_system',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'data_source' => 'array',
        'columns' => 'array',
        'filters' => 'array',
        'grouping' => 'array',
        'sorting' => 'array',
        'chart_config' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Categories
    const CATEGORY_ENROLLMENT = 'enrollment';
    const CATEGORY_ATTENDANCE = 'attendance';
    const CATEGORY_ACADEMIC = 'academic';
    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_STAFF = 'staff';
    const CATEGORY_DEMOGRAPHIC = 'demographic';

    const CATEGORIES = [
        self::CATEGORY_ENROLLMENT,
        self::CATEGORY_ATTENDANCE,
        self::CATEGORY_ACADEMIC,
        self::CATEGORY_FINANCIAL,
        self::CATEGORY_STAFF,
        self::CATEGORY_DEMOGRAPHIC,
    ];

    // Report Types
    const TYPE_TABLE = 'table';
    const TYPE_CHART = 'chart';
    const TYPE_SUMMARY = 'summary';
    const TYPE_DETAILED = 'detailed';

    const TYPES = [
        self::TYPE_TABLE,
        self::TYPE_CHART,
        self::TYPE_SUMMARY,
        self::TYPE_DETAILED,
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function savedReports(): HasMany
    {
        return $this->hasMany(SavedReport::class, 'definition_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    // Methods
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function duplicate(string $newName): self
    {
        $new = $this->replicate();
        $new->name = $newName;
        $new->code = $this->code . '_copy_' . time();
        $new->is_system = false;
        $new->save();
        return $new;
    }
}
