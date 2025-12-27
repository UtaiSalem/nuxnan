<?php

namespace App\Http\Controllers\Api\Learn\Student\HomeVisit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HomeVisitZone;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ZoneController extends Controller
{
    /**
     * Get all zones (for dropdown)
     */
    public function index(Request $request)
    {
        $query = HomeVisitZone::query();

        // Filter active only if requested
        if ($request->active_only) {
            $query->active();
        }

        $zones = $query->ordered()->get();

        return response()->json([
            'success' => true,
            'zones' => $zones,
        ]);
    }

    /**
     * Get all zones with pagination (for admin management)
     */
    public function list(Request $request)
    {
        if (!session('homevisit_admin_authenticated')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = HomeVisitZone::withCount('homeVisits');

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('zone_name', 'like', "%{$request->search}%")
                  ->orWhere('zone_code', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $zones = $query->ordered()->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'zones' => $zones,
        ]);
    }

    /**
     * Show specific zone
     */
    public function show($id)
    {
        if (!session('homevisit_admin_authenticated')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $zone = HomeVisitZone::withCount('homeVisits')->findOrFail($id);

        return response()->json([
            'success' => true,
            'zone' => $zone,
        ]);
    }

    /**
     * Create new zone (Admin only)
     */
    public function store(Request $request)
    {
        if (!session('homevisit_admin_authenticated')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'zone_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        
        // Generate zone_code from zone_name
        $data['zone_code'] = $this->generateZoneCode($data['zone_name']);

        $zone = HomeVisitZone::create($data);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มโซนเรียบร้อยแล้ว',
            'zone' => $zone,
        ], 201);
    }

    /**
     * Update zone (Admin only)
     */
    public function update(Request $request, $id)
    {
        if (!session('homevisit_admin_authenticated')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $zone = HomeVisitZone::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'zone_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        
        // Update zone_code if zone_name changed
        if ($data['zone_name'] !== $zone->zone_name) {
            $data['zone_code'] = $this->generateZoneCode($data['zone_name']);
        }

        $zone->update($data);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทโซนเรียบร้อยแล้ว',
            'zone' => $zone->fresh(),
        ]);
    }

    /**
     * Delete zone (Admin only)
     */
    public function destroy($id)
    {
        if (!session('homevisit_admin_authenticated')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $zone = HomeVisitZone::withCount('homeVisits')->findOrFail($id);

        // Check if zone has home visits
        if ($zone->home_visits_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบโซนที่มีการเยี่ยมบ้านอยู่ได้ กรุณาย้ายหรือลบการเยี่ยมบ้านก่อน',
            ], 400);
        }

        $zone->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบโซนเรียบร้อยแล้ว',
        ]);
    }

    /**
     * Toggle zone active status (Admin only)
     */
    public function toggleStatus($id)
    {
        if (!session('homevisit_admin_authenticated')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $zone = HomeVisitZone::findOrFail($id);
        $zone->is_active = !$zone->is_active;
        $zone->save();

        return response()->json([
            'success' => true,
            'message' => $zone->is_active ? 'เปิดใช้งานโซนแล้ว' : 'ปิดใช้งานโซนแล้ว',
            'zone' => $zone,
        ]);
    }

    /**
     * Reorder zones (Admin only)
     */
    public function reorder(Request $request)
    {
        if (!session('homevisit_admin_authenticated')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'zones' => 'required|array',
            'zones.*.id' => 'required|exists:home_visit_zones,id',
            'zones.*.display_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->zones as $zoneData) {
            HomeVisitZone::where('id', $zoneData['id'])
                ->update(['display_order' => $zoneData['display_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'จัดเรียงโซนเรียบร้อยแล้ว',
        ]);
    }

    /**
     * Generate unique zone code from zone name
     */
    private function generateZoneCode($zoneName)
    {
        // Remove special characters and convert to uppercase
        $baseCode = strtoupper(preg_replace('/[^a-zA-Z0-9\s]/', '', $zoneName));
        $baseCode = preg_replace('/\s+/', '_', trim($baseCode));
        
        // Limit to 50 characters
        $baseCode = substr($baseCode, 0, 50);
        
        // Check if code exists
        $code = $baseCode;
        $counter = 1;
        
        while (HomeVisitZone::where('zone_code', $code)->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;
        }
        
        return $code;
    }
}

