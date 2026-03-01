<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyStore;
use App\Models\StoreProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StoreProductController extends Controller
{
    /**
     * รายการสินค้าทั้งหมด (Public - สำหรับสมาชิก)
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        $store = AcademyStore::where('academy_id', $academy->id)->first();
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'ไม่พบร้านค้า'], 404);
        }

        $query = StoreProduct::where('academy_store_id', $store->id)
            ->with(['category:id,name,slug,icon']);

        // Filter: active only for non-admin
        if (!$request->boolean('include_inactive', false)) {
            $query->active();
        }

        // Filter: category
        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Filter: search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter: featured
        if ($request->boolean('featured', false)) {
            $query->featured();
        }

        // Filter: stock status
        if ($request->filled('stock_status')) {
            match ($request->stock_status) {
                'in_stock' => $query->inStock(),
                'low_stock' => $query->lowStock(),
                'out_of_stock' => $query->outOfStock(),
                default => null,
            };
        }

        // Filter: payment type
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['name', 'price', 'created_at', 'total_sold', 'average_rating', 'sort_order'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = $request->input('per_page', 20);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items() ? collect($products->items())->map(fn($p) => $this->formatProduct($p)) : [],
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ]);
    }

    /**
     * รายละเอียดสินค้า
     */
    public function show(Academy $academy, StoreProduct $product): JsonResponse
    {
        $product->load(['category', 'reviews' => function ($q) {
            $q->approved()->with('user:id,name,profile_photo_path')->latest()->limit(10);
        }]);

        return response()->json([
            'success' => true,
            'data' => $this->formatProduct($product, true),
        ]);
    }

    /**
     * สร้างสินค้าใหม่
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $store = AcademyStore::where('academy_id', $academy->id)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:100',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:store_categories,id',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'payment_type' => 'nullable|in:points,wallet,both',
            'points_price' => 'nullable|integer|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
        ]);

        // Handle image uploads
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('stores/products', 'public');
            }
        }

        $product = StoreProduct::create([
            'academy_store_id' => $store->id,
            'category_id' => $request->category_id,
            'created_by' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'compare_price' => $request->compare_price,
            'cost_price' => $request->cost_price,
            'sku' => $request->sku,
            'stock_quantity' => $request->stock_quantity,
            'low_stock_threshold' => $request->input('low_stock_threshold', 5),
            'images' => !empty($imagePaths) ? $imagePaths : null,
            'is_active' => $request->input('is_active', true),
            'is_featured' => $request->input('is_featured', false),
            'payment_type' => $request->input('payment_type', 'both'),
            'points_price' => $request->points_price,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatProduct($product),
            'message' => 'สร้างสินค้าเรียบร้อย'
        ], 201);
    }

    /**
     * อัพเดทสินค้า
     */
    public function update(Request $request, Academy $academy, StoreProduct $product): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:100',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:store_categories,id',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'payment_type' => 'nullable|in:points,wallet,both',
            'points_price' => 'nullable|integer|min:0',
        ]);

        $product->update($request->only([
            'name', 'description', 'price', 'compare_price', 'cost_price',
            'sku', 'stock_quantity', 'low_stock_threshold', 'category_id',
            'is_active', 'is_featured', 'payment_type', 'points_price',
        ]));

        return response()->json([
            'success' => true,
            'data' => $this->formatProduct($product->fresh()),
            'message' => 'อัพเดทสินค้าเรียบร้อย'
        ]);
    }

    /**
     * อัพโหลดรูปสินค้า
     */
    public function uploadImages(Request $request, Academy $academy, StoreProduct $product): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'image|max:2048',
        ]);

        $existingImages = $product->images ?? [];

        foreach ($request->file('images') as $image) {
            $existingImages[] = $image->store('stores/products', 'public');
        }

        $product->update(['images' => $existingImages]);

        return response()->json([
            'success' => true,
            'data' => ['images' => $product->image_urls],
            'message' => 'อัพโหลดรูปเรียบร้อย'
        ]);
    }

    /**
     * ลบรูปสินค้า
     */
    public function deleteImage(Request $request, Academy $academy, StoreProduct $product): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'image_index' => 'required|integer|min:0',
        ]);

        $images = $product->images ?? [];
        $index = $request->image_index;

        if (!isset($images[$index])) {
            return response()->json(['success' => false, 'message' => 'ไม่พบรูปภาพ'], 404);
        }

        // Delete file
        Storage::disk('public')->delete($images[$index]);

        // Remove from array
        array_splice($images, $index, 1);
        $product->update(['images' => !empty($images) ? $images : null]);

        return response()->json([
            'success' => true,
            'data' => ['images' => $product->image_urls],
            'message' => 'ลบรูปเรียบร้อย'
        ]);
    }

    /**
     * ลบสินค้า (Soft Delete)
     */
    public function destroy(Academy $academy, StoreProduct $product): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบสินค้าเรียบร้อย'
        ]);
    }

    /**
     * จัดรูปแบบข้อมูลสินค้า
     */
    protected function formatProduct(StoreProduct $product, bool $detailed = false): array
    {
        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $detailed ? $product->description : \Str::limit($product->description, 100),
            'price' => (float) $product->price,
            'compare_price' => $product->compare_price ? (float) $product->compare_price : null,
            'points_price' => $product->points_price,
            'payment_type' => $product->payment_type,
            'images' => $product->image_urls,
            'first_image' => $product->first_image_url,
            'stock_quantity' => $product->stock_quantity,
            'in_stock' => $product->isInStock(),
            'is_featured' => $product->is_featured,
            'is_on_sale' => $product->is_on_sale,
            'discount_percent' => $product->discount_percent,
            'average_rating' => (float) $product->average_rating,
            'review_count' => $product->review_count,
            'total_sold' => $product->total_sold,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
                'icon' => $product->category->icon,
            ] : null,
        ];

        if ($detailed) {
            $data['sku'] = $product->sku;
            $data['cost_price'] = $product->cost_price ? (float) $product->cost_price : null;
            $data['low_stock_threshold'] = $product->low_stock_threshold;
            $data['is_active'] = $product->is_active;
            $data['is_low_stock'] = $product->isLowStock();
            $data['created_at'] = $product->created_at;
            $data['reviews'] = $product->reviews ? $product->reviews->map(fn($r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'user' => $r->user ? [
                    'id' => $r->user->id,
                    'name' => $r->user->name,
                    'avatar' => $r->user->profile_photo_path,
                ] : null,
                'created_at' => $r->created_at,
            ]) : [];
        }

        return $data;
    }
}
