<?php

namespace App\Http\Controllers\Api\Learn\Course\info;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseSettingController extends Controller
{
    /**
     * Update marketplace settings for a course.
     */
    public function updateMarketplace(Request $request, Course $course): JsonResponse
    {
        // 1. ตรวจว่าเป็นเจ้าของ course เท่านั้น
        if (auth()->id() !== $course->user_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        // 2. Validate
        $validated = $request->validate([
            'is_for_marketplace' => 'required|boolean',
            'price_type'         => 'required_if:is_for_marketplace,true|in:free,points,wallet,both',
            'price'              => 'nullable|integer|min:0',
            'price_points'       => 'nullable|integer|min:0',
        ]);

        // 3. ถ้าเปิดขาย ต้องมีราคาสอดคล้องกับ price_type
        if ($validated['is_for_marketplace']) {
            $type = $validated['price_type'];
            if (in_array($type, ['wallet', 'both']) && empty($validated['price'])) {
                return response()->json(['success' => false, 'message' => 'Price (wallet) is required.'], 422);
            }
            if (in_array($type, ['points', 'both']) && empty($validated['price_points'])) {
                return response()->json(['success' => false, 'message' => 'Price (points) is required.'], 422);
            }
        }

        // 4. อัปเดต
        $course->update([
            'is_for_marketplace' => $validated['is_for_marketplace'],
            'price_type'         => $validated['price_type'] ?? 'free',
            'price'              => $validated['price'] ?? 0,
            'price_points'       => $validated['price_points'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => $course->is_for_marketplace ? 'Course listed on marketplace.' : 'Course removed from marketplace.',
            'data'    => [
                'is_for_marketplace' => $course->is_for_marketplace,
                'price_type'         => $course->price_type,
                'price'              => $course->price,
                'price_points'       => $course->price_points,
                'total_sales'        => $course->total_sales,
            ]
        ]);
    }

    public function setAutoAcceptMembers()
    {
        try {
            $courses = Course::all();

            foreach ($courses as $course) {
                $course->courseSettings()->create([
                    'auto_accept_members' => 1
                ]);
            }

            return response()->json([
                'success' => true
            ], 200);

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
