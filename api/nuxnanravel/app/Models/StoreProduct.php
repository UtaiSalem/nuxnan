<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StoreProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academy_store_id',
        'category_id',
        'created_by',
        'name',
        'slug',
        'description',
        'price',
        'compare_price',
        'cost_price',
        'sku',
        'stock_quantity',
        'low_stock_threshold',
        'images',
        'is_active',
        'is_featured',
        'payment_type',
        'points_price',
        'sort_order',
        'total_sold',
        'average_rating',
        'review_count',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'images' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'points_price' => 'integer',
        'sort_order' => 'integer',
        'total_sold' => 'integer',
        'average_rating' => 'decimal:2',
        'review_count' => 'integer',
        'metadata' => 'array',
    ];

    // ============================================================
    // Boot
    // ============================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . Str::random(5);
            }
        });
    }

    // ============================================================
    // Relationships
    // ============================================================

    public function store(): BelongsTo
    {
        return $this->belongsTo(AcademyStore::class, 'academy_store_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StoreCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(StoreOrderItem::class, 'store_product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(StoreProductReview::class, 'store_product_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%");
        });
    }

    // ============================================================
    // Accessors
    // ============================================================

    public function getFirstImageUrlAttribute(): ?string
    {
        $images = $this->images;
        if (empty($images) || !is_array($images)) return null;

        $first = $images[0];
        if (str_starts_with($first, 'http')) return $first;
        return asset('storage/' . $first);
    }

    public function getImageUrlsAttribute(): array
    {
        $images = $this->images ?? [];
        return array_map(function ($img) {
            if (str_starts_with($img, 'http')) return $img;
            return asset('storage/' . $img);
        }, $images);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function getDiscountPercentAttribute(): ?int
    {
        if (!$this->is_on_sale || $this->compare_price == 0) return null;
        return (int) round((($this->compare_price - $this->price) / $this->compare_price) * 100);
    }

    // ============================================================
    // Helpers
    // ============================================================

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity > 0 && $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function decrementStock(int $quantity = 1): bool
    {
        if ($this->stock_quantity < $quantity) return false;

        $this->decrement('stock_quantity', $quantity);
        $this->increment('total_sold', $quantity);
        return true;
    }

    public function incrementStock(int $quantity = 1): void
    {
        $this->increment('stock_quantity', $quantity);
    }

    public function updateAverageRating(): void
    {
        $reviews = $this->reviews()->where('is_approved', true);
        $this->update([
            'average_rating' => $reviews->avg('rating') ?? 0,
            'review_count' => $reviews->count(),
        ]);
    }
}
