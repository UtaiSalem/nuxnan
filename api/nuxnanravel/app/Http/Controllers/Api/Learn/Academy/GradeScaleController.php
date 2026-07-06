<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AssessmentCategory;
use App\Models\GradeScale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradeScaleController extends Controller
{
    /**
     * Get all grade scales for academy
     */
    public function index(int $academyId): JsonResponse
    {
        $gradeScales = GradeScale::where('academy_id', $academyId)
            ->with('items')
            ->get();

        // Create default if none exists
        if ($gradeScales->isEmpty()) {
            $default = GradeScale::createDefaultForAcademy($academyId);
            $gradeScales = collect([$default->load('items')]);
        }

        return response()->json([
            'success' => true,
            'gradeScales' => $gradeScales,
        ]);
    }

    /**
     * Get single grade scale
     */
    public function show(int $academyId, int $id): JsonResponse
    {
        $gradeScale = GradeScale::where('academy_id', $academyId)
            ->with('items')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'gradeScale' => $gradeScale,
        ]);
    }

    /**
     * Store new grade scale
     */
    public function store(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'is_default' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.grade' => 'required|string|max:5',
            'items.*.letter_grade' => 'nullable|string|max:5',
            'items.*.min_score' => 'required|numeric|min:0|max:100',
            'items.*.max_score' => 'required|numeric|min:0|max:100',
            'items.*.grade_points' => 'required|numeric|min:0|max:4',
            'items.*.description' => 'nullable|string|max:50',
        ]);

        $gradeScale = GradeScale::create([
            'academy_id' => $academyId,
            'name' => $validated['name'],
            'is_default' => $validated['is_default'] ?? false,
            'is_active' => true,
        ]);

        foreach ($validated['items'] as $index => $item) {
            $gradeScale->items()->create([
                'grade' => $item['grade'],
                'letter_grade' => $item['letter_grade'] ?? null,
                'min_score' => $item['min_score'],
                'max_score' => $item['max_score'],
                'grade_points' => $item['grade_points'],
                'description' => $item['description'] ?? null,
                'sort_order' => $index,
            ]);
        }

        if ($validated['is_default'] ?? false) {
            $gradeScale->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'สร้างมาตราส่วนเกรดสำเร็จ',
            'gradeScale' => $gradeScale->load('items'),
        ], 201);
    }

    /**
     * Update grade scale
     */
    public function update(Request $request, int $academyId, int $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $gradeScale = GradeScale::where('academy_id', $academyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:50',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'items' => 'array',
            'items.*.id' => 'nullable|exists:grade_scale_items,id',
            'items.*.grade' => 'required|string|max:5',
            'items.*.letter_grade' => 'nullable|string|max:5',
            'items.*.min_score' => 'required|numeric|min:0|max:100',
            'items.*.max_score' => 'required|numeric|min:0|max:100',
            'items.*.grade_points' => 'required|numeric|min:0|max:4',
            'items.*.description' => 'nullable|string|max:50',
        ]);

        $gradeScale->update([
            'name' => $validated['name'] ?? $gradeScale->name,
            'is_active' => $validated['is_active'] ?? $gradeScale->is_active,
        ]);

        // Update items if provided
        if (! empty($validated['items'])) {
            // Delete existing items
            $gradeScale->items()->delete();

            // Create new items
            foreach ($validated['items'] as $index => $item) {
                $gradeScale->items()->create([
                    'grade' => $item['grade'],
                    'letter_grade' => $item['letter_grade'] ?? null,
                    'min_score' => $item['min_score'],
                    'max_score' => $item['max_score'],
                    'grade_points' => $item['grade_points'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        }

        if (isset($validated['is_default']) && $validated['is_default']) {
            $gradeScale->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตมาตราส่วนเกรดสำเร็จ',
            'gradeScale' => $gradeScale->fresh('items'),
        ]);
    }

    /**
     * Delete grade scale
     */
    public function destroy(int $academyId, int $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $gradeScale = GradeScale::where('academy_id', $academyId)->findOrFail($id);

        if ($gradeScale->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบมาตราส่วนเริ่มต้นได้',
            ], 400);
        }

        $gradeScale->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบมาตราส่วนเกรดสำเร็จ',
        ]);
    }

    /**
     * Get assessment categories for academy
     */
    public function getCategories(int $academyId): JsonResponse
    {
        $categories = AssessmentCategory::where('academy_id', $academyId)
            ->active()
            ->ordered()
            ->get();

        // Create defaults if none exists
        if ($categories->isEmpty()) {
            AssessmentCategory::createDefaultsForAcademy($academyId);
            $categories = AssessmentCategory::where('academy_id', $academyId)
                ->active()
                ->ordered()
                ->get();
        }

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * Store assessment category
     */
    public function storeCategory(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'name_en' => 'nullable|string|max:50',
            'default_weight' => 'numeric|min:0|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        $maxOrder = AssessmentCategory::where('academy_id', $academyId)->max('sort_order') ?? 0;

        $category = AssessmentCategory::create([
            'academy_id' => $academyId,
            'name' => $validated['name'],
            'name_en' => $validated['name_en'] ?? null,
            'default_weight' => $validated['default_weight'] ?? 0,
            'icon' => $validated['icon'] ?? 'fluent:list-24-filled',
            'color' => $validated['color'] ?? 'gray',
            'sort_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้างหมวดหมู่สำเร็จ',
            'category' => $category,
        ], 201);
    }

    // Helper Methods
    protected function canManage(Academy $academy): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($academy->user_id === $user->id) {
            return true;
        }

        return $academy->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'director', 'admin'])
            ->exists();
    }
}
