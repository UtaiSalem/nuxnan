<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class MemberTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'slug',
        'color',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(AcademyMember::class, 'academy_member_tag')
            ->withPivot('assigned_by', 'created_at')
            ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForAcademy($query, int $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get member count
     */
    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Predefined colors for tags
     */
    public static function getAvailableColors(): array
    {
        return [
            '#ef4444' => 'แดง',
            '#f97316' => 'ส้ม',
            '#eab308' => 'เหลือง',
            '#22c55e' => 'เขียว',
            '#14b8a6' => 'เขียวอมฟ้า',
            '#06b6d4' => 'ฟ้า',
            '#3b82f6' => 'น้ำเงิน',
            '#6366f1' => 'ม่วงน้ำเงิน',
            '#8b5cf6' => 'ม่วง',
            '#ec4899' => 'ชมพู',
            '#f43f5e' => 'แดงชมพู',
            '#6b7280' => 'เทา',
        ];
    }
}
