<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyStore;
use App\Models\StoreCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StoreCategoryController extends Controller
{
    /**
     * รายการหมวดหมู่ทั้งหมด
     */
    public function index(Academy $academy): JsonResponse
    {
        $store = AcademyStore::where('academy_id', $academy->id)->first();
        if (! $store) {
            return response()->json(['success' => false, 'message' => 'ไม่พบร้านค้า'], 404);
        }

        $categories = StoreCategory::where('academy_store_id', $store->id)
            ->with('children')
            ->root()
            ->ordered()
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'icon' => $category->icon,
                    'sort_order' => $category->sort_order,
                    'is_active' => $category->is_active,
                    'products_count' => $category->products()->count(),
                    'children' => $category->children->map(fn ($child) => [
                        'id' => $child->id,
                        'name' => $child->name,
                        'slug' => $child->slug,
                        'icon' => $child->icon,
                        'is_active' => $child->is_active,
                        'products_count' => $child->products()->count(),
                    ]),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * สร้างหมวดหมู่ใหม่
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $store = AcademyStore::where('academy_id', $academy->id)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'parent_id' => 'nullable|exists:store_categories,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $slug = Str::slug($request->name);
        $existingSlug = StoreCategory::where('academy_store_id', $store->id)
            ->where('slug', $slug)->exists();

        if ($existingSlug) {
            $slug .= '-'.Str::random(4);
        }

        $category = StoreCategory::create([
            'academy_store_id' => $store->id,
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'icon' => $request->icon,
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'สร้างหมวดหมู่เรียบร้อย',
        ], 201);
    }

    /**
     * อัพเดทหมวดหมู่
     */
    public function update(Request $request, Academy $academy, StoreCategory $category): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->only(['name', 'description', 'icon', 'sort_order', 'is_active']);

        if (isset($data['name']) && $data['name'] !== $category->name) {
            $slug = Str::slug($data['name']);
            $existingSlug = StoreCategory::where('academy_store_id', $category->academy_store_id)
                ->where('slug', $slug)
                ->where('id', '!=', $category->id)
                ->exists();
            if ($existingSlug) {
                $slug .= '-'.Str::random(4);
            }
            $data['slug'] = $slug;
        }

        $category->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json([
            'success' => true,
            'data' => $category->fresh(),
            'message' => 'อัพเดทหมวดหมู่เรียบร้อย',
        ]);
    }

    /**
     * ลบหมวดหมู่
     */
    public function destroy(Academy $academy, StoreCategory $category): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Check if there are products in this category
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบหมวดหมู่ที่มีสินค้าอยู่ได้ กรุณาย้ายสินค้าก่อน',
            ], 422);
        }

        // Move children to parent (or root)
        $category->children()->update(['parent_id' => $category->parent_id]);

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบหมวดหมู่เรียบร้อย',
        ]);
    }
}
