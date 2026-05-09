<?php

namespace App\Http\Controllers\Api\Learn\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CoursePurchaseService;
use App\Http\Resources\Learn\Course\info\CourseResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CourseMarketplaceController extends Controller
{
    public function __construct(
        protected CoursePurchaseService $purchaseService
    ) {}

    /**
     * Get marketplace courses.
     * GET /api/courses/marketplace
     */
    public function index(Request $request): JsonResponse
    {
        $query = Course::forMarketplace()
            ->with(['user', 'academy'])
            ->withCount(['courseLessons', 'assignments', 'courseQuizzes']);

        // Filter: category
        if ($request->category) {
            $query->where('category', $request->category);
        }

        // Filter: level
        if ($request->level) {
            $query->where('level', $request->level);
        }

        // Filter: price_type
        if ($request->price_type && $request->price_type !== 'all') {
            $query->where('price_type', $request->price_type);
        }

        // Filter: search
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sort
        $sort = $request->sort ?? 'newest';
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'popular':
                $query->orderBy('total_sales', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $courses = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => CourseResource::collection($courses),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ]
        ]);
    }

    /**
     * Purchase a course copy.
     * POST /api/courses/{course}/purchase
     */
    public function purchase(Request $request, Course $course): JsonResponse
    {
        $request->validate([
            'payment_mode' => 'nullable|in:points,wallet,auto',
        ]);

        try {
            $buyer = Auth::user();
            $result = $this->purchaseService->purchase($buyer, $course, $request->payment_mode);

            return response()->json([
                'success' => true,
                'message' => $result['is_queued'] 
                    ? 'The course is being cloned. You will be notified when it is ready.' 
                    : 'Course purchased successfully.',
                'new_course_id' => $result['new_course'] ? $result['new_course']->id : null,
                'is_queued' => $result['is_queued'],
                'payment' => $result['payment']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get marketplace stats for a course.
     * GET /api/courses/marketplace/stats/{course}
     */
    public function stats(Course $course): JsonResponse
    {
        // Only owner or admin can see stats
        if (Auth::id() !== $course->user_id && !$course->isAdmin(Auth::user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => $course->total_sales,
                'is_for_marketplace' => $course->is_for_marketplace,
                'price' => $course->price,
                'price_points' => $course->price_points,
                'price_type' => $course->price_type,
            ]
        ]);
    }
}
