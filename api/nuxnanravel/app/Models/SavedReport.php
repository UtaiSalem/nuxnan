<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SavedReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'definition_id',
        'user_id',
        'name',
        'description',
        'parameters',
        'cached_data',
        'generated_at',
        'is_favorite',
        'is_shared',
    ];

    protected $casts = [
        'parameters' => 'array',
        'cached_data' => 'array',
        'generated_at' => 'datetime',
        'is_favorite' => 'boolean',
        'is_shared' => 'boolean',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'definition_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class, 'report_id');
    }

    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class, 'report_id');
    }

    // Scopes
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function toggleFavorite(): void
    {
        $this->update(['is_favorite' => !$this->is_favorite]);
    }

    public function share(): void
    {
        $this->update(['is_shared' => true]);
    }

    public function unshare(): void
    {
        $this->update(['is_shared' => false]);
    }

    public function updateCachedData(array $data): void
    {
        $this->update([
            'cached_data' => $data,
            'generated_at' => now(),
        ]);
    }

    public function clearCache(): void
    {
        $this->update([
            'cached_data' => null,
            'generated_at' => null,
        ]);
    }
}
