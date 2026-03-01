<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AcademyStoreController extends Controller
{
    /**
     * ดึงข้อมูลร้านค้าของโรงเรียน
     */
    public function show(Academy $academy): JsonResponse
    {
        $store = AcademyStore::where('academy_id', $academy->id)->first();

        if (!$store) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'ยังไม่มีร้านค้า'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $store->id,
                'academy_id' => $store->academy_id,
                'name' => $store->name,
                'description' => $store->description,
                'logo' => $store->logo_url,
                'banner_image' => $store->banner_url,
                'is_active' => $store->is_active,
                'allow_points_payment' => $store->allow_points_payment,
                'allow_wallet_payment' => $store->allow_wallet_payment,
                'min_order_amount' => $store->min_order_amount,
                'settings' => $store->settings,
                'stats' => $store->getStats(),
            ]
        ]);
    }

    /**
     * สร้างหรืออัพเดทร้านค้า
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'allow_points_payment' => 'nullable|boolean',
            'allow_wallet_payment' => 'nullable|boolean',
            'min_order_amount' => 'nullable|numeric|min:0',
        ]);

        $store = AcademyStore::updateOrCreate(
            ['academy_id' => $academy->id],
            [
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->input('is_active', true),
                'allow_points_payment' => $request->input('allow_points_payment', true),
                'allow_wallet_payment' => $request->input('allow_wallet_payment', true),
                'min_order_amount' => $request->input('min_order_amount', 0),
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $store,
            'message' => 'บันทึกร้านค้าเรียบร้อย'
        ]);
    }

    /**
     * อัพเดทตั้งค่าร้านค้า
     */
    public function update(Request $request, Academy $academy): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $store = AcademyStore::where('academy_id', $academy->id)->firstOrFail();

        $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'allow_points_payment' => 'nullable|boolean',
            'allow_wallet_payment' => 'nullable|boolean',
            'min_order_amount' => 'nullable|numeric|min:0',
            'settings' => 'nullable|array',
        ]);

        $store->update($request->only([
            'name', 'description', 'is_active',
            'allow_points_payment', 'allow_wallet_payment',
            'min_order_amount', 'settings',
        ]));

        return response()->json([
            'success' => true,
            'data' => $store->fresh(),
            'message' => 'อัพเดทร้านค้าเรียบร้อย'
        ]);
    }

    /**
     * อัพโหลดโลโก้ร้านค้า
     */
    public function uploadLogo(Request $request, Academy $academy): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'logo' => 'required|image|max:2048',
        ]);

        $store = AcademyStore::where('academy_id', $academy->id)->firstOrFail();

        // Delete old logo
        if ($store->logo) {
            Storage::disk('public')->delete($store->logo);
        }

        $path = $request->file('logo')->store('stores/logos', 'public');
        $store->update(['logo' => $path]);

        return response()->json([
            'success' => true,
            'data' => ['logo' => $store->logo_url],
            'message' => 'อัพโหลดโลโก้เรียบร้อย'
        ]);
    }

    /**
     * อัพโหลดแบนเนอร์
     */
    public function uploadBanner(Request $request, Academy $academy): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'banner' => 'required|image|max:4096',
        ]);

        $store = AcademyStore::where('academy_id', $academy->id)->firstOrFail();

        if ($store->banner_image) {
            Storage::disk('public')->delete($store->banner_image);
        }

        $path = $request->file('banner')->store('stores/banners', 'public');
        $store->update(['banner_image' => $path]);

        return response()->json([
            'success' => true,
            'data' => ['banner_image' => $store->banner_url],
            'message' => 'อัพโหลดแบนเนอร์เรียบร้อย'
        ]);
    }

    /**
     * สถิติร้านค้า
     */
    public function stats(Academy $academy): JsonResponse
    {
        $store = AcademyStore::where('academy_id', $academy->id)->first();

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบร้านค้า'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $store->getStats()
        ]);
    }
}
