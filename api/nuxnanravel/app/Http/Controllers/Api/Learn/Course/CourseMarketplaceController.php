<?php

namespace App\Http\Controllers\Api\Learn\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CoursePurchaseService;
use App\Http\Resources\Learn\Course\info\MarketplaceCourseResource;
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
        $currentUserId = Auth::guard('api')->id();

        $query = Course::where(function ($q) {
                $q->where('is_for_marketplace', true)
                  ->orWhere('saleable', true);
            })
            ->with([
                'user', 
                'academy', 
                'courseMembers' => function($q) use ($currentUserId) {
                    $q->where('user_id', $currentUserId);
                },
                'favorites' => function($q) use ($currentUserId) {
                    $q->where('user_id', $currentUserId);
                }
            ])
            ->withCount([
                'courseLessons', 
                'courseAssignments', 
                'courseQuizzes',
                'courseMembers as enrolled_students_count' => function($q) {
                    $q->where('status', 1);
                }
            ]);

        // Text
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        // Categorical
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('level')) $query->where('level', $request->level);
        if ($request->filled('education_level')) $query->where('education_level', $request->education_level);
        if ($request->filled('education_year')) $query->where('education_year', $request->education_year);
        if ($request->filled('language')) $query->where('language', $request->language);
        if ($request->filled('semester')) $query->where('semester', $request->semester);
        if ($request->filled('academic_year')) $query->where('academic_year', $request->academic_year);

        // Ranges
        if ($request->filled('credit_units_min')) $query->where('credit_units', '>=', $request->credit_units_min);
        if ($request->filled('credit_units_max')) $query->where('credit_units', '<=', $request->credit_units_max);
        if ($request->filled('hours_per_week_min')) $query->where('hours_per_week', '>=', $request->hours_per_week_min);
        if ($request->filled('hours_per_week_max')) $query->where('hours_per_week', '<=', $request->hours_per_week_max);

        // Price
        if ($request->boolean('is_free')) {
            $query->where('price', 0);
        } else {
            if ($request->filled('price_min')) $query->where('price', '>=', $request->price_min);
            if ($request->filled('price_max')) $query->where('price', '<=', $request->price_max);
        }

        // Rating
        if ($request->filled('rating_min')) $query->where('rating', '>=', $request->rating_min);

        // Sort
        match ($request->sort ?? 'newest') {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'popular' => $query->orderBy('total_sales', 'desc'),
            'rating' => $query->orderBy('rating', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $courses = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => MarketplaceCourseResource::collection($courses),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
        ]);
    }

    /**
     * Purchase a course copy.
     * POST /api/courses/{course}/purchase
     */
    public function purchase(Request $request, Course $course): JsonResponse
    {
        $request->validate([
            'payment_mode' => 'nullable|in:wallet,points,mixed',
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
            ]
        ]);
    }
}
