<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidget extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'code',
        'name',
        'widget_type',
        'category',
        'data_source',
        'display_config',
        'refresh_interval',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'data_source' => 'array',
        'display_config' => 'array',
        'refresh_interval' => 'integer',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Widget Types
    const TYPE_COUNTER = 'counter';
    const TYPE_CHART = 'chart';
    const TYPE_TABLE = 'table';
    const TYPE_PROGRESS = 'progress';
    const TYPE_LIST = 'list';

    const TYPES = [
        self::TYPE_COUNTER,
        self::TYPE_CHART,
        self::TYPE_TABLE,
        self::TYPE_PROGRESS,
        self::TYPE_LIST,
    ];

    // Categories
    const CATEGORY_OVERVIEW = 'overview';
    const CATEGORY_ACADEMIC = 'academic';
    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_ATTENDANCE = 'attendance';
    const CATEGORY_STAFF = 'staff';

    const CATEGORIES = [
        self::CATEGORY_OVERVIEW,
        self::CATEGORY_ACADEMIC,
        self::CATEGORY_FINANCIAL,
        self::CATEGORY_ATTENDANCE,
        self::CATEGORY_STAFF,
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
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

    public function scopeByType($query, string $type)
    {
        return $query->where('widget_type', $type);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
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

    public function getDisplaySize(): array
    {
        return $this->display_config['size'] ?? ['width' => 1, 'height' => 1];
    }

    public function getChartType(): ?string
    {
        return $this->display_config['chart_type'] ?? null;
    }
}
