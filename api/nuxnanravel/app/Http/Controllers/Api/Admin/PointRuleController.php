<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PointRuleController extends Controller
{
    /**
     * Display a listing of the point rules.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $rules = PointRule::all();

        return response()->json([
            'success' => true,
            'data' => [
                'rules' => $rules,
            ],
        ]);
    }

    /**
     * Store a newly created point rule.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'rule_key' => 'required|string|max:100|unique:point_rules,rule_key',
            'rule_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'action_type' => 'required|in:earn,spend',
            'source_type' => 'required|string|max:50',
            'base_amount' => 'required|numeric|min:0',
            'multiplier' => 'nullable|numeric|min:0|max:10',
            'xp_amount' => 'nullable|integer|min:0',
            'max_daily_earnings' => 'nullable|integer|min:0',
            'max_monthly_earnings' => 'nullable|integer|min:0',
            'cooldown_minutes' => 'nullable|integer|min:0',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
            'is_active' => 'boolean',
        ]);

        $rule = PointRule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Rule created successfully',
            'data' => [
                'rule' => $rule,
            ],
        ], 201);
    }

    /**
     * Display the specified point rule.
     */
    public function show(int $id): JsonResponse
    {
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $rule = PointRule::find($id);
        if (!$rule) {
            return response()->json(['success' => false, 'message' => 'Rule not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'rule' => $rule,
            ],
        ]);
    }

    /**
     * Update the specified point rule.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $rule = PointRule::find($id);
        if (!$rule) {
            return response()->json(['success' => false, 'message' => 'Rule not found'], 404);
        }

        $validated = $request->validate([
            'rule_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'base_amount' => 'nullable|numeric|min:0',
            'multiplier' => 'nullable|numeric|min:0|max:10',
            'xp_amount' => 'nullable|integer|min:0',
            'max_daily_earnings' => 'nullable|integer|min:0',
            'max_monthly_earnings' => 'nullable|integer|min:0',
            'cooldown_minutes' => 'nullable|integer|min:0',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
            'is_active' => 'boolean',
        ]);

        $rule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Rule updated successfully',
            'data' => [
                'rule' => $rule,
            ],
        ]);
    }

    /**
     * Remove the specified point rule.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $rule = PointRule::find($id);
        if (!$rule) {
            return response()->json(['success' => false, 'message' => 'Rule not found'], 404);
        }

        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rule deleted successfully',
        ]);
    }

    /**
     * Toggle the active status of a point rule.
     */
    public function toggle(int $id): JsonResponse
    {
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $rule = PointRule::find($id);
        if (!$rule) {
            return response()->json(['success' => false, 'message' => 'Rule not found'], 404);
        }

        $rule->update(['is_active' => !$rule->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Rule status toggled successfully',
            'data' => [
                'rule' => $rule,
            ],
        ]);
    }
}
