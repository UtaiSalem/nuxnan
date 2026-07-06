<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyStore;
use App\Models\StoreOrder;
use App\Services\SchoolStoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreOrderController extends Controller
{
    protected SchoolStoreService $storeService;

    public function __construct(SchoolStoreService $storeService)
    {
        $this->storeService = $storeService;
    }

    /**
     * รายการคำสั่งซื้อ (Admin view)
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        $store = AcademyStore::where('academy_id', $academy->id)->first();
        if (! $store) {
            return response()->json(['success' => false, 'message' => 'ไม่พบร้านค้า'], 404);
        }

        $query = StoreOrder::where('academy_store_id', $store->id)
            ->with(['user:id,name,profile_photo_path', 'items']);

        // Filter by status
        if ($request->filled('status')) {
            $query->status($request->status);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        // Search by order number
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', '%'.$request->search.'%');
            });
        }

        $orders = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => collect($orders->items())->map(fn ($o) => $this->formatOrder($o)),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * คำสั่งซื้อของผู้ใช้ (My Orders)
     */
    public function myOrders(Request $request, Academy $academy): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $store = AcademyStore::where('academy_id', $academy->id)->first();
        if (! $store) {
            return response()->json(['success' => false, 'message' => 'ไม่พบร้านค้า'], 404);
        }

        $orders = StoreOrder::where('academy_store_id', $store->id)
            ->forUser($user->id)
            ->with('items.product:id,name,images')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => collect($orders->items())->map(fn ($o) => $this->formatOrder($o)),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * รายละเอียดคำสั่งซื้อ
     */
    public function show(Academy $academy, StoreOrder $order): JsonResponse
    {
        $order->load([
            'user:id,name,profile_photo_path',
            'items.product:id,name,images,slug',
            'processedByUser:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatOrder($order, true),
        ]);
    }

    /**
     * สร้างคำสั่งซื้อ
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $store = AcademyStore::where('academy_id', $academy->id)->first();
        if (! $store || ! $store->is_active) {
            return response()->json(['success' => false, 'message' => 'ร้านค้าไม่เปิดให้บริการ'], 404);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:store_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:points,wallet',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $order = $this->storeService->createOrder(
                $store,
                $user,
                $request->items,
                $request->payment_method,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'data' => $this->formatOrder($order),
                'message' => 'สั่งซื้อเรียบร้อย',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ยืนยันคำสั่งซื้อ (Admin)
     */
    public function confirm(Academy $academy, StoreOrder $order): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $order = $this->storeService->confirmOrder($order, $user);

            return response()->json([
                'success' => true,
                'data' => $this->formatOrder($order),
                'message' => 'ยืนยันคำสั่งซื้อเรียบร้อย',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * เปลี่ยนสถานะเป็น Processing (Admin)
     */
    public function process(Academy $academy, StoreOrder $order): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $order = $this->storeService->processOrder($order);

            return response()->json([
                'success' => true,
                'data' => $this->formatOrder($order),
                'message' => 'เปลี่ยนสถานะเป็นกำลังดำเนินการ',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * เปลี่ยนสถานะเป็น Ready (Admin)
     */
    public function markReady(Academy $academy, StoreOrder $order): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $order = $this->storeService->markReady($order);

            return response()->json([
                'success' => true,
                'data' => $this->formatOrder($order),
                'message' => 'สินค้าพร้อมรับแล้ว',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * เสร็จสิ้นคำสั่งซื้อ (Admin)
     */
    public function complete(Academy $academy, StoreOrder $order): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $order = $this->storeService->completeOrder($order);

            return response()->json([
                'success' => true,
                'data' => $this->formatOrder($order),
                'message' => 'เสร็จสิ้นคำสั่งซื้อ',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * ยกเลิกคำสั่งซื้อ
     */
    public function cancel(Request $request, Academy $academy, StoreOrder $order): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $order = $this->storeService->cancelOrder($order, $request->reason, $user);

            return response()->json([
                'success' => true,
                'data' => $this->formatOrder($order),
                'message' => 'ยกเลิกคำสั่งซื้อเรียบร้อย',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * รายงานยอดขาย (Admin)
     */
    public function salesReport(Request $request, Academy $academy): JsonResponse
    {
        $store = AcademyStore::where('academy_id', $academy->id)->first();
        if (! $store) {
            return response()->json(['success' => false, 'message' => 'ไม่พบร้านค้า'], 404);
        }

        $report = $this->storeService->getSalesReport(
            $store,
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * จัดรูปแบบข้อมูลคำสั่งซื้อ
     */
    protected function formatOrder(StoreOrder $order, bool $detailed = false): array
    {
        $data = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'status_color' => $order->status_color,
            'subtotal' => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'total' => (float) $order->total,
            'payment_method' => $order->payment_method,
            'points_spent' => $order->points_spent,
            'payment_status' => $order->payment_status,
            'items_count' => $order->items->count(),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'product' => $item->product ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'slug' => $item->product->slug,
                    'first_image' => $item->product->first_image_url,
                ] : null,
            ]),
            'user' => $order->user ? [
                'id' => $order->user->id,
                'name' => $order->user->name,
                'avatar' => $order->user->profile_photo_path,
            ] : null,
            'created_at' => $order->created_at,
        ];

        if ($detailed) {
            $data['notes'] = $order->notes;
            $data['cancel_reason'] = $order->cancel_reason;
            $data['confirmed_at'] = $order->confirmed_at;
            $data['completed_at'] = $order->completed_at;
            $data['cancelled_at'] = $order->cancelled_at;
            $data['processed_by'] = $order->processedByUser ? [
                'id' => $order->processedByUser->id,
                'name' => $order->processedByUser->name,
            ] : null;
        }

        return $data;
    }
}
