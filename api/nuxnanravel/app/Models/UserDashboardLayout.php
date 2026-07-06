<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDashboardLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'user_id',
        'dashboard_type',
        'widget_layout',
        'is_default',
    ];

    protected $casts = [
        'widget_layout' => 'array',
        'is_default' => 'boolean',
    ];

    // Dashboard Types
    const TYPE_MAIN = 'main';

    const TYPE_ACADEMIC = 'academic';

    const TYPE_FINANCIAL = 'financial';

    const TYPE_STAFF = 'staff';

    const TYPES = [
        self::TYPE_MAIN,
        self::TYPE_ACADEMIC,
        self::TYPE_FINANCIAL,
        self::TYPE_STAFF,
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('dashboard_type', $type);
    }

    // Methods
    public function getWidgets(): array
    {
        return $this->widget_layout['widgets'] ?? [];
    }

    public function addWidget(int $widgetId, array $position): void
    {
        $widgets = $this->getWidgets();
        $widgets[] = [
            'id' => $widgetId,
            'position' => $position,
        ];
        $this->update(['widget_layout' => ['widgets' => $widgets]]);
    }

    public function removeWidget(int $widgetId): void
    {
        $widgets = collect($this->getWidgets())->filter(function ($widget) use ($widgetId) {
            return $widget['id'] !== $widgetId;
        })->values()->toArray();

        $this->update(['widget_layout' => ['widgets' => $widgets]]);
    }

    public function updateWidgetPosition(int $widgetId, array $position): void
    {
        $widgets = collect($this->getWidgets())->map(function ($widget) use ($widgetId, $position) {
            if ($widget['id'] === $widgetId) {
                $widget['position'] = $position;
            }

            return $widget;
        })->toArray();

        $this->update(['widget_layout' => ['widgets' => $widgets]]);
    }

    public function resetToDefault(): void
    {
        // Get default system widgets for this academy
        $defaultWidgets = DashboardWidget::where('academy_id', $this->academy_id)
            ->where('is_system', true)
            ->where('is_active', true)
            ->get()
            ->map(function ($widget, $index) {
                return [
                    'id' => $widget->id,
                    'position' => ['x' => $index % 4, 'y' => floor($index / 4)],
                ];
            })
            ->toArray();

        $this->update(['widget_layout' => ['widgets' => $defaultWidgets]]);
    }
}
