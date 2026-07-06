<?php

namespace App\Http\Controllers\Api\Learn\Course\reviews;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\reviews\CourseReviewResource;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseReviewController extends Controller
{
    /**
     * Display a listing of reviews for a course.
     */
    public function index(Course $course, Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $reviews = $course->reviews()
            ->approved()
            ->with('user:id,name,profile_photo_path,reference_code')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Get rating distribution
        $ratingDistribution = $course->reviews()
            ->approved()
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Fill in missing ratings with 0
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = $ratingDistribution[$i] ?? 0;
        }

        return response()->json([
            'success' => true,
            'reviews' => CourseReviewResource::collection($reviews),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
            'summary' => [
                'average_rating' => round($course->rating ?? 0, 1),
                'total_reviews' => $course->reviews()->approved()->count(),
                'distribution' => $distribution,
            ],
        ]);
    }

    /**
     * Store a new review or update existing one.
     */
    public function store(Request $request, Course $course)
    {
        $user = auth('api')->user();

        // Check if user is a course member
        $isMember = CourseMember::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('course_member_status', 1) // 1 = active member
            ->exists();

        if (! $isMember) {
            return response()->json([
                'success' => false,
                'message' => 'คุณต้องเป็นสมาชิกของรายวิชานี้เพื่อรีวิว',
            ], 403);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:2000',
        ]);

        // Create or update review
        $review = CourseReview::updateOrCreate(
            [
                'course_id' => $course->id,
                'user_id' => $user->id,
            ],
            [
                'rating' => $validated['rating'],
                'title' => $validated['title'] ?? null,
                'content' => $validated['content'] ?? null,
                'is_approved' => true,
            ]
        );

        // Update course average rating
        $this->updateCourseRating($course);

        return response()->json([
            'success' => true,
            'message' => $review->wasRecentlyCreated ? 'รีวิวของคุณถูกบันทึกแล้ว' : 'รีวิวของคุณถูกอัปเดตแล้ว',
            'review' => new CourseReviewResource($review->load('user')),
        ]);
    }

    /**
     * Display the specified review.
     */
    public function show(Course $course, CourseReview $review)
    {
        if ($review->course_id !== $course->id) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'review' => new CourseReviewResource($review->load('user')),
        ]);
    }

    /**
     * Update the specified review.
     */
    public function update(Request $request, Course $course, CourseReview $review)
    {
        $user = auth('api')->user();

        // Check ownership
        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์แก้ไขรีวิวนี้',
            ], 403);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:2000',
        ]);

        $review->update([
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'] ?? null,
        ]);

        // Update course average rating
        $this->updateCourseRating($course);

        return response()->json([
            'success' => true,
            'message' => 'รีวิวของคุณถูกอัปเดตแล้ว',
            'review' => new CourseReviewResource($review->load('user')),
        ]);
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Course $course, CourseReview $review)
    {
        $user = auth('api')->user();

        // Check ownership or admin
        $isCourseAdmin = CourseMember::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->exists();

        if ($review->user_id !== $user->id && ! $isCourseAdmin && $course->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ลบรีวิวนี้',
            ], 403);
        }

        $review->delete();

        // Update course average rating
        $this->updateCourseRating($course);

        return response()->json([
            'success' => true,
            'message' => 'รีวิวถูกลบแล้ว',
        ]);
    }

    /**
     * Get authenticated user's review for this course.
     */
    public function myReview(Course $course)
    {
        $user = auth('api')->user();

        $review = CourseReview::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $review) {
            return response()->json([
                'success' => true,
                'review' => null,
                'can_review' => $this->canUserReview($course, $user),
            ]);
        }

        return response()->json([
            'success' => true,
            'review' => new CourseReviewResource($review->load('user')),
            'can_review' => true,
        ]);
    }

    /**
     * Check if user can review this course.
     */
    private function canUserReview(Course $course, $user): bool
    {
        return CourseMember::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('course_member_status', 1) // 1 = active member
            ->exists();
    }

    /**
     * Update course average rating.
     */
    private function updateCourseRating(Course $course): void
    {
        $averageRating = $course->reviews()
            ->approved()
            ->avg('rating');

        $course->update([
            'rating' => $averageRating ? round($averageRating, 2) : null,
        ]);
    }
}
