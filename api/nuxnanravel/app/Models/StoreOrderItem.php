<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_order_id',
        'store_product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'unit_points_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'unit_points_price' => 'integer',
        'total_price' => 'decimal:2',
    ];

    // ============================================================
    // Relationships
    // ============================================================

    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(StoreProduct::class, 'store_product_id');
    }
}
