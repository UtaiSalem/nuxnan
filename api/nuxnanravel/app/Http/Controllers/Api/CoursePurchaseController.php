<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CoursePurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoursePurchaseController extends Controller
{
    protected $purchaseService;

    public function __construct(CoursePurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    /**
     * Get marketplace courses.
     */
    public function index(Request $request)
    {
        $query = Course::forMarketplace()
            ->with(['user:id,name,profile_photo_path'])
            ->withCount(['courseLessons', 'assignments', 'courseQuizzes']);

        // Filters
        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->level) {
            $query->where('level', $request->level);
        }
        if ($request->price_type && $request->price_type !== 'all') {
            $query->where('price_type', $request->price_type);
        }

        // Search
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sorting
        $sort = $request->sort ?? 'newest';
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'popular':
                $query->orderBy('total_sales', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $courses = $query->paginate($request->per_page ?? 12);

        return response()->json($courses);
    }

    /**
     * Purchase a course.
     */
    public function purchase(Request $request, Course $course)
    {
        $request->validate([
            'payment_mode' => 'required|in:points,wallet,auto',
        ]);

        try {
            $buyer = Auth::user();
            $result = $this->purchaseService->purchase($buyer, $course, $request->payment_mode);

            return response()->json([
                'message' => 'Course purchased successfully.',
                'course' => $result['course'],
                'payment_mode' => $result['payment_mode']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get marketplace stats for a course.
     */
    public function stats(Course $course)
    {
        // Only owner can see stats
        if (Auth::id() !== $course->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'total_sales' => $course->total_sales,
            'is_for_marketplace' => $course->is_for_marketplace,
            'price' => $course->price,
            'price_points' => $course->price_points,
            'price_type' => $course->price_type,
        ]);
    }
}
