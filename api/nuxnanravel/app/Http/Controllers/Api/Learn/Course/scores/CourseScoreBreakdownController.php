<?php

namespace App\Http\Controllers\Api\Learn\Course\scores;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;
use App\Models\Course;
use App\Services\CourseScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CourseScoreBreakdownController extends Controller
{
    protected CourseScoreService $scoreService;

    /**
     * Lazy-resync threshold for member scores when listing the page.
     * Members whose last_score_synced_at is older than this (or null) are recomputed.
     */
    private const STALE_SCORE_THRESHOLD_MINUTES = 5;

    public function __construct(CourseScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    /**
     * Audit gradebook for a course — paginated, filterable, sortable.
     *
     * Query params:
     *  - page (int, default 1)
     *  - per_page (int, default 20, max 100)
     *  - group_id (int | "all" | "ungrouped")
     *  - search (string, matches member_name / user.name / user.username / member_code)
     *  - sort (name | percentage | grade | last_synced_at | order_number, default order_number)
     *  - order (asc | desc, default asc)
     */
    public function index(Request $request, Course $course): JsonResponse
    {
        if (! $course->hasPermission(auth()->user(), 'edit_grades')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = (int) min(max((int) $request->get('per_page', 20), 1), 100);
        $sortField = (string) $request->get('sort', 'order_number');
        $sortOrder = strtolower((string) $request->get('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        // Learner roles only: 1=student, 2=student_leader (aligned with progress endpoint)
        $query = $course->courseMembers()
            ->whereIn('role', [1, 2])
            ->with('user:id,name,username,profile_photo_path');

        // Group filter
        $groupId = $request->get('group_id');
        if ($groupId && $groupId !== 'all') {
            if ($groupId === 'ungrouped') {
                $query->whereNull('group_id');
            } else {
                $query->where('group_id', $groupId);
            }
        }

        // Search
        if ($search = trim((string) $request->get('search', ''))) {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('member_name', 'like', $term)
                    ->orWhere('member_code', 'like', $term)
                    ->orWhereHas('user', function ($uq) use ($term) {
                        $uq->where('name', 'like', $term)
                            ->orWhere('username', 'like', $term);
                    });
            });
        }

        // Sorting (computed sorts map to existing DB columns to stay paginate-safe)
        switch ($sortField) {
            case 'name':
                $query->leftJoin('users', 'course_members.user_id', '=', 'users.id')
                    ->orderByRaw('COALESCE(course_members.member_name, users.name) '.($sortOrder === 'desc' ? 'DESC' : 'ASC'))
                    ->select('course_members.*');
                break;
            case 'percentage':
                // grade_progress is derived from percentage in CourseScoreService::recompute,
                // so it's a stable proxy without a JOIN.
                $query->orderBy('grade_progress', $sortOrder);
                break;
            case 'grade':
                $query->orderBy('grade_progress', $sortOrder);
                break;
            case 'last_synced_at':
                $query->orderBy('last_score_synced_at', $sortOrder);
                break;
            case 'order_number':
            default:
                $query->orderBy('order_number', $sortOrder);
                break;
        }

        $paginated = $query->paginate($perPage);

        // Lazy-sync stale members on the current page only
        $staleCutoff = now()->subMinutes(self::STALE_SCORE_THRESHOLD_MINUTES);
        foreach ($paginated->items() as $member) {
            $syncedAt = $member->last_score_synced_at
                ? ($member->last_score_synced_at instanceof \DateTimeInterface
                    ? Carbon::instance($member->last_score_synced_at)
                    : Carbon::parse($member->last_score_synced_at))
                : null;

            if (! $syncedAt || $syncedAt->lt($staleCutoff)) {
                $this->scoreService->recompute($member);
                $member->refresh();
            }
        }

        $data = [];
        $withMissingOnPage = 0;
        foreach ($paginated->items() as $member) {
            $breakdown = $this->scoreService->computeBreakdown($member);
            $breakdownArr = $breakdown->toArray();
            $user = $member->user;

            $missing = $breakdownArr['missing_sources'] ?? [];
            $hasMissing = ! empty($missing['quiz_ids'] ?? [])
                || ! empty($missing['course_assignment_ids'] ?? [])
                || ! empty($missing['lesson_assignment_ids'] ?? []);
            if ($hasMissing) {
                $withMissingOnPage++;
            }

            $data[] = [
                'member_id' => $member->id,
                'user' => [
                    'id' => $user?->id,
                    'name' => $user?->name ?? 'Unknown user',
                    'username' => $user?->username,
                    'avatar' => $this->getAvatarUrl($user),
                    'profile_photo_path' => $user?->profile_photo_path,
                ],
                'breakdown' => $breakdownArr,
                'last_synced_at' => $member->last_score_synced_at,
                'grade_progress' => $member->grade_progress,
                'draft_grade' => $member->draft_grade,
                'final_grade' => $member->final_grade,
                'has_missing_sources' => $hasMissing,
            ];
        }

        // Course-wide stats (independent of page) — learners only
        $base = $course->courseMembers()->whereIn('role', [1, 2]);
        $totalMembers = (clone $base)->count();
        $syncedMembers = (clone $base)->whereNotNull('last_score_synced_at')->count();
        $lastFullSyncAt = (clone $base)->max('last_score_synced_at');

        // Group summary
        $groupCounts = (clone $base)->selectRaw('group_id, COUNT(*) as c')
            ->groupBy('group_id')
            ->pluck('c', 'group_id');
        $ungroupedCount = $groupCounts[null] ?? $groupCounts[''] ?? 0;

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
            'groups' => CourseGroupResource::collection($course->courseGroups),
            'ungrouped_count' => $ungroupedCount,
            'stats' => [
                'total_members' => $totalMembers,
                'synced_members' => $syncedMembers,
                'with_missing_sources_on_page' => $withMissingOnPage,
                'last_full_sync_at' => $lastFullSyncAt,
            ],
            'use_legacy_gradebook' => $course->use_legacy_gradebook,
            'course_total_score' => $course->total_score,
        ]);
    }

    private function getAvatarUrl($user): string
    {
        if (! $user) {
            return 'https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF';
        }

        if ($user->profile_photo_path) {
            if (filter_var($user->profile_photo_path, FILTER_VALIDATE_URL)) {
                return $user->profile_photo_path;
            }

            return url(Storage::url($user->profile_photo_path));
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($user->name ?? 'User').'&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Explicitly resync all member scores for a course.
     */
    public function resync(Request $request, Course $course): JsonResponse
    {
        if (! $course->hasPermission(auth()->user(), 'edit_grades')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->scoreService->syncAllCourseMembers($course);

        return response()->json([
            'success' => true,
            'message' => 'Resynced scores for all members successfully.',
        ]);
    }
}
