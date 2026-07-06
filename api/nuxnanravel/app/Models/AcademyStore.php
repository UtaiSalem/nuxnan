<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademyStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'description',
        'logo',
        'banner_image',
        'is_active',
        'allow_points_payment',
        'allow_wallet_payment',
        'min_order_amount',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_points_payment' => 'boolean',
        'allow_wallet_payment' => 'boolean',
        'min_order_amount' => 'decimal:2',
        'settings' => 'array',
    ];

    // ============================================================
    // Relationships
    // ============================================================

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(StoreCategory::class, 'academy_store_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(StoreProduct::class, 'academy_store_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'academy_store_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ============================================================
    // Accessors
    // ============================================================

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }
        if (str_starts_with($this->logo, 'http')) {
            return $this->logo;
        }

        return asset('storage/'.$this->logo);
    }

    public function getBannerUrlAttribute(): ?string
    {
        if (! $this->banner_image) {
            return null;
        }
        if (str_starts_with($this->banner_image, 'http')) {
            return $this->banner_image;
        }

        return asset('storage/'.$this->banner_image);
    }

    // ============================================================
    // Helpers
    // ============================================================

    public function getStats(): array
    {
        return [
            'total_products' => $this->products()->count(),
            'active_products' => $this->products()->where('is_active', true)->count(),
            'low_stock_products' => $this->products()
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->where('is_active', true)
                ->count(),
            'total_orders' => $this->orders()->count(),
            'pending_orders' => $this->orders()->where('status', 'pending')->count(),
            'total_revenue' => $this->orders()
                ->where('payment_status', 'paid')
                ->sum('total'),
            'total_categories' => $this->categories()->where('is_active', true)->count(),
        ];
    }
}
