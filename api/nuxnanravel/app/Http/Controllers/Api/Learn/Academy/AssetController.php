<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Learn\Academy\AssetMaintenanceLog;
use App\Models\Learn\Academy\SchoolAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssetController extends Controller
{
    public function index(Academy $academy, Request $request): JsonResponse
    {
        $query = SchoolAsset::where('academy_id', $academy->id);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $assets = $query->orderBy('asset_code')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $assets,
        ]);
    }

    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'asset_code' => 'required|string|unique:school_assets,asset_code',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric',
            'location' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $validated['academy_id'] = $academy->id;

        $asset = SchoolAsset::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Asset registered successfully',
            'data' => $asset,
        ], 201);
    }

    public function requestMaintenance(Request $request, Academy $academy, SchoolAsset $asset): JsonResponse
    {
        $validated = $request->validate([
            'issue_description' => 'required|string',
        ]);

        $log = AssetMaintenanceLog::create([
            'academy_id' => $academy->id,
            'school_asset_id' => $asset->id,
            'requested_by' => Auth::id() ?? 1,
            'issue_description' => $validated['issue_description'],
            'status' => 'pending',
        ]);

        $asset->update(['status' => 'maintenance']);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request submitted',
            'data' => $log,
        ]);
    }
}
