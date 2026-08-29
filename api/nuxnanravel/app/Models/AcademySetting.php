<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class AcademySetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'show_member_list' => 'boolean',
        'show_course_list' => 'boolean',
        'card_request_flow_enabled' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Keep Academy::getSettings() cache in sync when a setting changes
        // directly (Academy::updated only fires when the academy row is dirty).
        $forget = fn ($setting) => Cache::forget("academy_settings_{$setting->academy_id}");

        static::saved($forget);
        static::deleted($forget);
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }
}
