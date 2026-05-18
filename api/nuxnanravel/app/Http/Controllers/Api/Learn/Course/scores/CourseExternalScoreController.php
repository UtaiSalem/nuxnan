<?php

namespace App\Http\Controllers\Api\Learn\Course\scores;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseExternalScore;
use App\Models\CourseExternalScoreEntry;
use App\Services\CourseScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseExternalScoreController extends Controller
{
    protected $scoreService;

    public function __construct(CourseScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    /**
     * แสดงรายการหัวข้อคะแนนจากภายนอกทั้งหมดของรายวิชา
     */
    public function index(Course $course)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $externalScores = $course->load('courseGroups')
            ->courseExternalScores()
            ->with(['creator:id,name,profile_photo_path', 'group:id,name', 'entries'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($score) {
                return [
                    'id' => $score->id,
                    'title' => $score->title,
                    'description' => $score->description,
                    'category' => $score->category,
                    'max_score' => (float)$score->max_score,
                    'group_id' => $score->group_id,
                    'group_name' => $score->group?->name,
                    'scored_at' => $score->scored_at?->format('Y-m-d'),
                    'is_active' => $score->is_active,
                    'entries_count' => $score->entries->count(),
                    'average_score' => $score->entries->count() > 0
                        ? round($score->entries->avg('score'), 2) : 0,
                    'creator' => $score->creator ? [
                        'id' => $score->creator->id,
                        'name' => $score->creator->name,
                    ] : null,
                    'created_at' => $score->created_at->toISOString(),
                ];
            });

        $groups = $course->courseGroups()->select('id', 'name')->get();

        return response()->json([
            'success' => true,
            'external_scores' => $externalScores,
            'groups' => $groups,
        ]);
    }

    /**
     * สร้างหัวข้อคะแนนจากภายนอกใหม่
     */
    public function store(Course $course, Request $request)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|in:exam,activity,project,other',
            'max_score' => 'required|numeric|min:0|max:99999',
            'scored_at' => 'nullable|date',
        ]);

        $externalScore = CourseExternalScore::create([
            'course_id' => $course->id,
            'created_by' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? 'other',
            'max_score' => $validated['max_score'],
            'group_id' => null,
            'scored_at' => $validated['scored_at'] ?? now()->toDateString(),
        ]);

        // Sync course total score
        $this->scoreService->syncCourseTotalScore($course);

        return response()->json([
            'success' => true,
            'message' => 'สร้างหัวข้อคะแนนเรียบร้อยแล้ว',
            'external_score' => $externalScore,
        ], 201);
    }

    /**
     * แสดงรายละเอียดหัวข้อคะแนน พร้อมรายชื่อนักเรียนและคะแนน
     */
    public function show(Course $course, CourseExternalScore $externalScore)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($externalScore->course_id !== $course->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        // ดึงสมาชิกทั้งหมดของรายวิชา (ไม่รวม admin)
        $members = $course->courseMembers()
            ->where('role', '!=', 4)
            ->with('user:id,name,profile_photo_path')
            ->orderBy('order_number')
            ->get();

        // ดึง entries ที่มีอยู่แล้ว
        $entries = $externalScore->entries()->get()->keyBy('course_member_id');

        $membersList = $members->map(function ($member) use ($entries) {
            $entry = $entries->get($member->id);
            $user = $member->user;

            return [
                'course_member_id' => $member->id,
                'user_id' => $member->user_id,
                'name' => $member->member_name ?? $user?->name ?? 'Unknown',
                'avatar' => $this->getAvatarUrl($user),
                'order_number' => $member->order_number,
                'member_code' => $member->member_code,
                'group_id' => $member->group_id,
                'score' => $entry ? (float) $entry->score : null,
                'note' => $entry?->note,
                'entry_id' => $entry?->id,
            ];
        });

        $groups = $course->courseGroups()->select('id', 'name')->get();

        return response()->json([
            'success' => true,
            'external_score' => [
                'id' => $externalScore->id,
                'title' => $externalScore->title,
                'description' => $externalScore->description,
                'category' => $externalScore->category,
                'max_score' => (float)$externalScore->max_score,
                'group_id' => $externalScore->group_id,
                'scored_at' => $externalScore->scored_at?->format('Y-m-d'),
                'is_active' => $externalScore->is_active,
            ],
            'members' => $membersList,
            'groups' => $groups,
        ]);
    }

    /**
     * อัปเดตหัวข้อคะแนน
     */
    public function update(Course $course, CourseExternalScore $externalScore, Request $request)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($externalScore->course_id !== $course->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|in:exam,activity,project,other',
            'max_score' => 'sometimes|required|numeric|min:0|max:99999',
            'scored_at' => 'nullable|date',
        ]);

        $externalScore->update($validated);

        // Sync course total score if max_score changed
        if ($request->has('max_score')) {
            $this->scoreService->syncCourseTotalScore($course);
            // Also need to update all members grade progress because total_score changed
            $this->scoreService->syncAllCourseMembers($course);
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตหัวข้อคะแนนเรียบร้อยแล้ว',
            'external_score' => $externalScore->fresh(),
        ]);
    }

    /**
     * ลบหัวข้อคะแนน (พร้อม entries ทั้งหมด)
     */
    public function destroy(Course $course, CourseExternalScore $externalScore)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($externalScore->course_id !== $course->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        // เก็บ member IDs ที่มี entries เพื่ออัปเดตคะแนนหลังลบ
        $affectedMemberIds = $externalScore->entries()->pluck('course_member_id')->toArray();

        $externalScore->delete(); // cascade ลบ entries ด้วย

        // Sync course total score
        $this->scoreService->syncCourseTotalScore($course);

        // อัปเดต external_score_points ให้สมาชิกที่ได้รับผลกระทบ
        foreach ($affectedMemberIds as $memberId) {
            $member = CourseMember::find($memberId);
            if ($member) {
                $this->scoreService->syncMemberExternalScore($member);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'ลบหัวข้อคะแนนเรียบร้อยแล้ว',
        ]);
    }

    /**
     * บันทึกคะแนนนักเรียนแบบ bulk (ทั้งหมดของหัวข้อนั้น)
     */
    public function saveEntries(Course $course, CourseExternalScore $externalScore, Request $request)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($externalScore->course_id !== $course->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'entries' => 'required|array',
            'entries.*.course_member_id' => 'required|exists:course_members,id',
            'entries.*.score' => 'nullable|numeric|min:0|max:' . $externalScore->max_score,
            'entries.*.note' => 'nullable|string|max:500',
        ]);

        $affectedMemberIds = [];

        DB::transaction(function () use ($externalScore, $validated, &$affectedMemberIds) {
            foreach ($validated['entries'] as $entryData) {
                $memberId = $entryData['course_member_id'];
                $score = $entryData['score'];
                $note = $entryData['note'] ?? null;

                if ($score === null || $score === '') {
                    // ลบ entry ถ้าคะแนนเป็น null
                    $externalScore->entries()
                        ->where('course_member_id', $memberId)
                        ->delete();
                } else {
                    CourseExternalScoreEntry::updateOrCreate(
                        [
                            'external_score_id' => $externalScore->id,
                            'course_member_id' => $memberId,
                        ],
                        [
                            'score' => $score,
                            'note' => $note,
                            'scored_by' => auth()->id(),
                        ]
                    );
                }

                $affectedMemberIds[] = $memberId;
            }
        });

        // Sync scores สำหรับสมาชิกที่ได้รับผลกระทบ
        foreach (array_unique($affectedMemberIds) as $memberId) {
            $member = CourseMember::find($memberId);
            if ($member) {
                $this->scoreService->syncMemberExternalScore($member);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'บันทึกคะแนนเรียบร้อยแล้ว',
        ]);
    }

    /**
     * แสดงข้อมูลแบบตาราง: สมาชิกเป็นแถว, หัวข้อคะแนนเป็นคอลัมน์
     */
    public function tableView(Course $course, $groupId = null)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $groups = $course->courseGroups()->select('id', 'name')->withCount('members')->get();

        // ดึง external scores ทั้งหมดของ course
        $externalScores = $course->courseExternalScores()
            ->where('is_active', true)
            ->orderBy('created_at', 'asc')
            ->get();

        // ดึงสมาชิก (ไม่รวม admin role=4) กรองตาม group เฉพาะการแสดงผล
        $membersQuery = $course->courseMembers()
            ->where('role', '!=', 4)
            ->with('user:id,name,profile_photo_path', 'group:id,name');

        if ($groupId) {
            $membersQuery->where('group_id', $groupId);
        }

        $members = $membersQuery->orderBy('order_number')->get();

        // ดึง entries ทั้งหมดของ external scores เหล่านี้
        $allEntries = CourseExternalScoreEntry::whereIn('external_score_id', $externalScores->pluck('id'))
            ->whereIn('course_member_id', $members->pluck('id'))
            ->get()
            ->groupBy('course_member_id');

        // สร้างข้อมูลสมาชิก
        $membersData = $members->map(function ($member) use ($allEntries, $externalScores) {
            $memberEntries = $allEntries->get($member->id, collect());
            $entriesByScore = $memberEntries->keyBy('external_score_id');

            $scores = [];
            foreach ($externalScores as $es) {
                $entry = $entriesByScore->get($es->id);
                $scores[$es->id] = [
                    'score' => $entry ? (float) $entry->score : null,
                    'note' => $entry?->note,
                    'entry_id' => $entry?->id,
                ];
            }

            $user = $member->user;
            return [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'name' => $member->member_name ?? $user?->name ?? 'Unknown',
                'avatar' => $this->getAvatarUrl($user),
                'order_number' => $member->order_number,
                'member_code' => $member->member_code,
                'group_id' => $member->group_id,
                'group' => $member->group ? ['id' => $member->group->id, 'name' => $member->group->name] : null,
                'scores' => $scores,
                'external_score_points' => (float)$member->external_score_points,
                'bonus_points' => (float)$member->bonus_points,
                'achieved_score' => (float)$member->achieved_score,
            ];
        });

        // ข้อมูลคอลัมน์ (external scores)
        $columns = $externalScores->map(function ($es) {
            return [
                'id' => $es->id,
                'title' => $es->title,
                'description' => $es->description,
                'category' => $es->category,
                'max_score' => (float) $es->max_score,
                'group_id' => $es->group_id,
                'scored_at' => $es->scored_at?->format('Y-m-d'),
                'entries_count' => $es->entries()->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'columns' => $columns,
            'members' => $membersData,
            'groups' => $groups,
        ]);
    }

    /**
     * คำนวณและ sync bonus_points ใน course_members จาก external scores ทั้งหมด
     */
    private function syncBonusPointsForMembers(Course $course, array $memberIds): void
    {
        $uniqueIds = array_unique($memberIds);

        foreach ($uniqueIds as $memberId) {
            $totalExternal = CourseExternalScoreEntry::whereHas('externalScore', function ($q) use ($course) {
                    $q->where('course_id', $course->id)->where('is_active', true);
                })
                ->where('course_member_id', $memberId)
                ->sum('score');

            $member = CourseMember::find($memberId);
            if ($member) {
                $member->update(['bonus_points' => (int) round($totalExternal)]);

                // Recalculate grade
                $this->recalculateGrade($course, $member);
            }
        }
    }

    /**
     * คำนวณเกรดใหม่สำหรับสมาชิก (ตามแพทเทิร์นเดิมของ updateBonusPoints)
     */
    private function recalculateGrade(Course $course, CourseMember $member): void
    {
        $lessons = $course->courseLessons()->with(['assignments', 'questions'])->get();

        $computedMaxTotal = $course->courseAssignments->sum('points')
            + $lessons->flatMap->assignments->sum('points')
            + $course->courseQuizzes->sum('total_score')
            + $lessons->flatMap->questions->sum('points');

        $totalScore = ($member->achieved_score ?? 0) + ($member->bonus_points ?? 0);

        $percentage = ($computedMaxTotal > 0)
            ? ($totalScore / $computedMaxTotal) * 100
            : 0;
        $percentage = min(100, max(0, $percentage));

        $grade = CourseMember::calculateGradeFromPercentage($percentage);

        $member->update(['grade_progress' => $grade]);
    }

    private function getAvatarUrl($user): string
    {
        if (!$user) {
            return 'https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF';
        }

        if ($user->profile_photo_path) {
            if (filter_var($user->profile_photo_path, FILTER_VALIDATE_URL)) {
                return $user->profile_photo_path;
            }
            return url(\Illuminate\Support\Facades\Storage::url($user->profile_photo_path));
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'User') . '&color=7F9CF5&background=EBF4FF';
    }
}
