<?php

namespace App\Services;

use App\Models\AcademyStore;
use App\Models\StoreOrder;
use App\Models\StoreProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchoolStoreService
{
    protected PointsService $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * สร้างคำสั่งซื้อจากรายการสินค้า
     */
    public function createOrder(AcademyStore $store, User $user, array $items, string $paymentMethod, ?string $notes = null): StoreOrder
    {
        return DB::transaction(function () use ($store, $user, $items, $paymentMethod, $notes) {
            // Validate stock availability
            $this->validateStock($items);

            // Calculate totals
            $subtotal = 0;
            $totalPoints = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $product = StoreProduct::findOrFail($item['product_id']);

                if (!$product->is_active) {
                    throw new \Exception("สินค้า '{$product->name}' ไม่พร้อมจำหน่าย");
                }

                if ($product->academy_store_id !== $store->id) {
                    throw new \Exception("สินค้า '{$product->name}' ไม่ได้อยู่ในร้านนี้");
                }

                $quantity = $item['quantity'] ?? 1;
                $unitPrice = (float) $product->price;
                $unitPointsPrice = $product->points_price;
                $totalPrice = $unitPrice * $quantity;

                $subtotal += $totalPrice;
                if ($paymentMethod === 'points' && $unitPointsPrice) {
                    $totalPoints += $unitPointsPrice * $quantity;
                }

                $orderItems[] = [
                    'store_product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'unit_points_price' => $unitPointsPrice,
                    'total_price' => $totalPrice,
                ];
            }

            // Validate payment
            if ($paymentMethod === 'points') {
                if (!$store->allow_points_payment) {
                    throw new \Exception('ร้านค้านี้ไม่รับชำระด้วยพ้อยท์');
                }
                if ($user->pp < $totalPoints) {
                    throw new \Exception('พ้อยท์ไม่เพียงพอ ต้องการ ' . $totalPoints . ' คุณมี ' . $user->pp);
                }
            } elseif ($paymentMethod === 'wallet') {
                if (!$store->allow_wallet_payment) {
                    throw new \Exception('ร้านค้านี้ไม่รับชำระด้วยกระเป๋าเงิน');
                }
                if ($user->wallet < $subtotal) {
                    throw new \Exception('ยอดเงินในกระเป๋าไม่เพียงพอ ต้องการ ' . $subtotal . ' คุณมี ' . $user->wallet);
                }
            }

            // Min order check
            if ($store->min_order_amount > 0 && $subtotal < $store->min_order_amount) {
                throw new \Exception('ยอดสั่งซื้อขั้นต่ำ ' . $store->min_order_amount . ' บาท');
            }

            // Process payment
            if ($paymentMethod === 'points') {
                $transaction = $this->pointsService->spend(
                    $user,
                    $totalPoints,
                    'store_purchase',
                    null,
                    'ซื้อสินค้าจากร้าน ' . $store->name,
                    ['store_id' => $store->id]
                );

                if (!$transaction) {
                    throw new \Exception('ไม่สามารถหักพ้อยท์ได้');
                }
            } elseif ($paymentMethod === 'wallet') {
                $user->decrement('wallet', $subtotal);
            }

            // Create order
            $order = StoreOrder::create([
                'academy_store_id' => $store->id,
                'user_id' => $user->id,
                'status' => StoreOrder::STATUS_PENDING,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'total' => $subtotal,
                'payment_method' => $paymentMethod,
                'points_spent' => $paymentMethod === 'points' ? $totalPoints : null,
                'payment_status' => StoreOrder::PAYMENT_PAID,
                'notes' => $notes,
            ]);

            // Create order items
            foreach ($orderItems as $orderItem) {
                $order->items()->create($orderItem);
            }

            // Decrement stock
            foreach ($items as $item) {
                $product = StoreProduct::find($item['product_id']);
                $product->decrementStock($item['quantity'] ?? 1);
            }

            Log::info('Store order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'store_id' => $store->id,
                'user_id' => $user->id,
                'total' => $subtotal,
                'payment_method' => $paymentMethod,
            ]);

            return $order->load('items');
        });
    }

    /**
     * ยืนยันคำสั่งซื้อ (by store admin)
     */
    public function confirmOrder(StoreOrder $order, User $processedBy): StoreOrder
    {
        if ($order->status !== StoreOrder::STATUS_PENDING) {
            throw new \Exception('ไม่สามารถยืนยันคำสั่งซื้อสถานะ ' . $order->status_label);
        }

        $order->update([
            'status' => StoreOrder::STATUS_CONFIRMED,
            'processed_by' => $processedBy->id,
            'confirmed_at' => now(),
        ]);

        return $order->fresh();
    }

    /**
     * เปลี่ยนสถานะเป็น Processing
     */
    public function processOrder(StoreOrder $order): StoreOrder
    {
        if ($order->status !== StoreOrder::STATUS_CONFIRMED) {
            throw new \Exception('ต้องยืนยันคำสั่งซื้อก่อน');
        }

        $order->update(['status' => StoreOrder::STATUS_PROCESSING]);
        return $order->fresh();
    }

    /**
     * เปลี่ยนสถานะเป็น Ready
     */
    public function markReady(StoreOrder $order): StoreOrder
    {
        if ($order->status !== StoreOrder::STATUS_PROCESSING) {
            throw new \Exception('ต้องกำลังดำเนินการก่อน');
        }

        $order->update(['status' => StoreOrder::STATUS_READY]);
        return $order->fresh();
    }

    /**
     * เสร็จสิ้นคำสั่งซื้อ
     */
    public function completeOrder(StoreOrder $order): StoreOrder
    {
        if (!$order->canBeCompleted()) {
            throw new \Exception('ไม่สามารถเสร็จสิ้นคำสั่งซื้อสถานะ ' . $order->status_label);
        }

        $order->update([
            'status' => StoreOrder::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return $order->fresh();
    }

    /**
     * ยกเลิกคำสั่งซื้อ พร้อมคืนเงิน/พ้อยท์
     */
    public function cancelOrder(StoreOrder $order, string $reason, User $cancelledBy): StoreOrder
    {
        return DB::transaction(function () use ($order, $reason, $cancelledBy) {
            if (!$order->canBeCancelled()) {
                throw new \Exception('ไม่สามารถยกเลิกคำสั่งซื้อได้ สถานะปัจจุบัน: ' . $order->status_label);
            }

            // Refund payment
            if ($order->isPaid()) {
                $user = $order->user;

                if ($order->payment_method === 'points' && $order->points_spent) {
                    $this->pointsService->earn(
                        $user,
                        $order->points_spent,
                        'store_refund',
                        $order->id,
                        'คืนพ้อยท์จากยกเลิกคำสั่งซื้อ #' . $order->order_number
                    );
                } elseif ($order->payment_method === 'wallet') {
                    $user->increment('wallet', $order->total);
                }

                $order->update(['payment_status' => StoreOrder::PAYMENT_REFUNDED]);
            }

            // Restore stock
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->incrementStock($item->quantity);
                }
            }

            $order->update([
                'status' => StoreOrder::STATUS_CANCELLED,
                'cancel_reason' => $reason,
                'cancelled_at' => now(),
                'processed_by' => $cancelledBy->id,
            ]);

            Log::info('Store order cancelled', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'reason' => $reason,
                'cancelled_by' => $cancelledBy->id,
            ]);

            return $order->fresh();
        });
    }

    /**
     * ตรวจสอบสต็อก
     */
    protected function validateStock(array $items): void
    {
        foreach ($items as $item) {
            $product = StoreProduct::findOrFail($item['product_id']);
            $quantity = $item['quantity'] ?? 1;

            if ($product->stock_quantity < $quantity) {
                throw new \Exception(
                    "สินค้า '{$product->name}' มีสต็อกไม่เพียงพอ (เหลือ {$product->stock_quantity} ชิ้น)"
                );
            }
        }
    }

    /**
     * สรุปยอดขาย
     */
    public function getSalesReport(AcademyStore $store, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = $store->orders()->where('payment_status', StoreOrder::PAYMENT_PAID);

        if ($startDate) $query->where('created_at', '>=', $startDate);
        if ($endDate) $query->where('created_at', '<=', $endDate);

        $orders = $query->get();

        return [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total'),
            'total_points_spent' => $orders->whereNotNull('points_spent')->sum('points_spent'),
            'average_order_value' => $orders->count() > 0 ? round($orders->avg('total'), 2) : 0,
            'orders_by_status' => $store->orders()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'top_products' => StoreProduct::where('academy_store_id', $store->id)
                ->orderByDesc('total_sold')
                ->limit(10)
                ->get(['id', 'name', 'total_sold', 'price', 'stock_quantity']),
        ];
    }
}
