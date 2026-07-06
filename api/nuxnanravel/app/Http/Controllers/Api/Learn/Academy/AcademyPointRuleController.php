<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Learn\Academy\AcademyPointRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademyPointRuleController extends Controller
{
    /**
     * List point rules for academy
     */
    public function index(Academy $academy): JsonResponse
    {
        $rules = AcademyPointRule::where('academy_id', $academy->id)->get();

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    /**
     * Store new point rule
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'rule_type' => 'required|string|in:attendance,behavior,event,milestone',
            'rule_key' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points' => 'required|numeric',
            'daily_cap' => 'nullable|integer|min:0',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['academy_id'] = $academy->id;

        $rule = AcademyPointRule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Point rule created successfully',
            'data' => $rule,
        ], 201);
    }

    /**
     * Update point rule
     */
    public function update(Request $request, Academy $academy, AcademyPointRule $rule): JsonResponse
    {
        if ($rule->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'points' => 'sometimes|numeric',
            'daily_cap' => 'nullable|integer|min:0',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $rule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Point rule updated successfully',
            'data' => $rule->fresh(),
        ]);
    }

    /**
     * Delete point rule
     */
    public function destroy(Academy $academy, AcademyPointRule $rule): JsonResponse
    {
        if ($rule->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Point rule deleted successfully',
        ]);
    }
}
