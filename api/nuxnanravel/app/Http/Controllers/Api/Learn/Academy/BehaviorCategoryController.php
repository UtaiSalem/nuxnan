<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\Behavior\StoreBehaviorCategoryRequest;
use App\Models\Academy;
use App\Models\Learn\Academy\BehaviorCategory;
use App\Traits\ManagesEventPermissions;

class BehaviorCategoryController extends Controller
{
    use ManagesEventPermissions;

    public function index(Academy $academy)
    {
        $categories = BehaviorCategory::where('academy_id', $academy->id)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function store(StoreBehaviorCategoryRequest $request, Academy $academy)
    {
        // ตรวจสิทธิ์: ต้องเป็น academy admin
        if (! $this->isAcademyAdmin($request->user(), $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $category = BehaviorCategory::create(
            array_merge($request->validated(), ['academy_id' => $academy->id])
        );

        return response()->json(['success' => true, 'data' => $category, 'message' => 'สร้างหมวดหมู่สำเร็จ'], 201);
    }

    public function update(StoreBehaviorCategoryRequest $request, Academy $academy, BehaviorCategory $category)
    {
        if (! $this->isAcademyAdmin($request->user(), $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $category->update($request->validated());

        return response()->json(['success' => true, 'data' => $category, 'message' => 'อัปเดตหมวดหมู่สำเร็จ']);
    }

    public function destroy(Academy $academy, BehaviorCategory $category)
    {
        if (! $this->isAcademyAdmin(request()->user(), $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Soft toggle — ไม่ลบจริง เปลี่ยน is_active เป็น false
        $category->update(['is_active' => ! $category->is_active]);

        return response()->json(['success' => true, 'message' => $category->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน']);
    }
}
