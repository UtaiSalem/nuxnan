<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\StoreProduct;
use App\Models\StoreProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreReviewController extends Controller
{
    /**
     * รีวิวของสินค้า
     */
    public function index(Request $request, Academy $academy, StoreProduct $product): JsonResponse
    {
        $reviews = StoreProductReview::where('store_product_id', $product->id)
            ->approved()
            ->with('user:id,name,profile_photo_path')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => collect($reviews->items())->map(fn($r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'user' => $r->user ? [
                    'id' => $r->user->id,
                    'name' => $r->user->name,
                    'avatar' => $r->user->profile_photo_path,
                ] : null,
                'created_at' => $r->created_at,
            ]),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'average_rating' => (float) $product->average_rating,
                'review_count' => $product->review_count,
            ]
        ]);
    }

    /**
     * เขียนรีวิว
     */
    public function store(Request $request, Academy $academy, StoreProduct $product): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'order_id' => 'nullable|exists:store_orders,id',
        ]);

        // Check if already reviewed
        $existing = StoreProductReview::where('store_product_id', $product->id)
            ->where('user_id', $user->id)
            ->when($request->order_id, fn($q) => $q->where('store_order_id', $request->order_id))
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'คุณได้รีวิวสินค้านี้แล้ว'
            ], 422);
        }

        $review = StoreProductReview::create([
            'store_product_id' => $product->id,
            'user_id' => $user->id,
            'store_order_id' => $request->order_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Update product average rating
        $product->updateAverageRating();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
            ],
            'message' => 'รีวิวเรียบร้อย'
        ], 201);
    }

    /**
     * ลบรีวิว (Admin)
     */
    public function destroy(Academy $academy, StoreProduct $product, StoreProductReview $review): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $review->delete();
        $product->updateAverageRating();

        return response()->json([
            'success' => true,
            'message' => 'ลบรีวิวเรียบร้อย'
        ]);
    }
}
