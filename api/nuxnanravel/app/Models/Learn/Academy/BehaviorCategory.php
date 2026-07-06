<?php

namespace App\Models\Learn\Academy;

use App\Models\Academy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BehaviorCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'description',
        'type',
        'default_points',
        'severity',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_points' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(BehaviorRecord::class, 'category_id');
    }
}
