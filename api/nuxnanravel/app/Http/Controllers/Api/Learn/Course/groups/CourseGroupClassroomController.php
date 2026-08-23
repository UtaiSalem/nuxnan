<?php

namespace App\Http\Controllers\Api\Learn\Course\groups;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseGroupClassroom;
use App\Services\Course\CourseClassroomRosterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseGroupClassroomController extends Controller
{
    public function __construct(
        private CourseClassroomRosterService $rosterService
    ) {}

    private function validateAccess(Course $course)
    {
        if ($course->academy_id === null) {
            abort(422, 'รายวิชานี้ไม่ได้สังกัดโรงเรียน');
        }

        if (! $course->isAdmin(auth()->user())) {
            abort(403, 'ไม่มีสิทธิ์ในการจัดการรายวิชานี้');
        }
    }

    private function validateGroup(Course $course, CourseGroup $group)
    {
        if ($group->course_id !== $course->id) {
            abort(404, 'ไม่พบกลุ่มในรายวิชานี้');
        }
    }

    public function index(Request $request, Course $course)
    {
        $this->validateAccess($course);

        $academicYearId = $request->input('academic_year_id') ? (int) $request->input('academic_year_id') : null;

        $classrooms = $this->rosterService->availableClassrooms(auth()->user(), $course, $academicYearId);

        $scope = $this->rosterService->scopeFor(auth()->user(), $course);

        $academicYears = DB::table('academic_years')
            ->where('academy_id', $course->academy_id)
            ->orderByDesc('name')
            ->get();

        if ($academicYearId === null) {
            $currentYear = $academicYears->firstWhere('is_current', 1);
            $academicYearId = $currentYear ? $currentYear->id : null;
        }

        return response()->json([
            'success' => true,
            'academy' => ['id' => $course->academy->id, 'name' => $course->academy->name],
            'scope' => $scope,
            'academic_years' => $academicYears->map(function ($y) {
                return ['id' => $y->id, 'year' => $y->name, 'is_current' => (bool) $y->is_current];
            }),
            'selected_academic_year_id' => $academicYearId,
            'classrooms' => $classrooms,
        ]);
    }

    public function importFromClassrooms(Request $request, Course $course)
    {
        $this->validateAccess($course);

        $validated = $request->validate([
            'classroom_ids' => 'required|array|min:1',
            'classroom_ids.*' => 'integer|exists:classrooms,id',
            'mode' => 'required|in:per_classroom,single_group',
            'group_name' => 'required_if:mode,single_group|string|max:255',
            'dry_run' => 'boolean',
        ]);

        $this->rosterService->assertCanUseClassrooms(auth()->user(), $course, $validated['classroom_ids']);

        $isDryRun = $request->boolean('dry_run');
        $mode = $validated['mode'];
        $groupName = $request->input('group_name');

        if ($isDryRun) {
            $preview = $this->rosterService->preview(auth()->user(), $course, $validated['classroom_ids'], $mode, $groupName);

            $toAdd = 0;
            $alreadyMember = 0;
            $noUserAccount = 0;
            $moving = 0;

            foreach ($preview as $item) {
                $toAdd += count($item['to_add']);
                $alreadyMember += count($item['already_member']);
                $noUserAccount += count($item['no_user_account']);
                $moving += count($item['moving_from_other_group']);
            }

            return response()->json([
                'success' => true,
                'dry_run' => true,
                'summary' => [
                    'classrooms' => count($validated['classroom_ids']),
                    'total_students' => $toAdd + $alreadyMember + $noUserAccount + $moving,
                    'to_add' => $toAdd,
                    'already_member' => $alreadyMember,
                    'no_user_account' => $noUserAccount,
                    'moving_from_other_group' => $moving,
                ],
                'items' => $preview,
            ]);
        } else {
            // คำนวณก่อนลงมือ เพื่อให้ summary บอกว่า "เพิ่งทำอะไรไป" ไม่ใช่สภาพหลังทำเสร็จ
            $preview = $this->rosterService->preview(auth()->user(), $course, $validated['classroom_ids'], $mode, $groupName);

            $result = $this->rosterService->apply(auth()->user(), $course, $validated['classroom_ids'], $mode, $groupName);

            $toAdd = 0;
            $alreadyMember = 0;
            $noUserAccount = 0;
            $moving = 0;

            foreach ($preview as $item) {
                $toAdd += count($item['to_add']);
                $alreadyMember += count($item['already_member']);
                $noUserAccount += count($item['no_user_account']);
                $moving += count($item['moving_from_other_group']);
            }

            return response()->json([
                'success' => true,
                'dry_run' => false,
                'summary' => [
                    'classrooms' => count($validated['classroom_ids']),
                    'total_students' => $toAdd + $alreadyMember + $noUserAccount + $moving,
                    'to_add' => $toAdd,
                    'already_member' => $alreadyMember,
                    'no_user_account' => $noUserAccount,
                    'moving_from_other_group' => $moving,
                ],
                'items' => $preview,
                'groups' => CourseGroupResource::collection(
                    CourseGroup::whereIn('id', collect($result['groups'])->pluck('id'))
                        ->with('members.user')
                        ->get()
                ),
            ]);
        }
    }

    public function linkClassrooms(Request $request, Course $course, CourseGroup $group)
    {
        $this->validateAccess($course);
        $this->validateGroup($course, $group);

        $validated = $request->validate([
            'classroom_ids' => 'present|array',
            'classroom_ids.*' => 'integer|exists:classrooms,id',
        ]);

        if (! empty($validated['classroom_ids'])) {
            $this->rosterService->assertCanUseClassrooms(auth()->user(), $course, $validated['classroom_ids']);
        }

        DB::transaction(function () use ($group, $validated) {
            CourseGroupClassroom::where('course_group_id', $group->id)->delete();

            if (! empty($validated['classroom_ids'])) {
                $classrooms = Classroom::whereIn('id', $validated['classroom_ids'])->get()->keyBy('id');

                foreach ($validated['classroom_ids'] as $classroomId) {
                    $classroom = $classrooms->get($classroomId);
                    CourseGroupClassroom::create([
                        'course_group_id' => $group->id,
                        'classroom_id' => $classroomId,
                        'academic_year_id' => $classroom->academic_year_id,
                        'created_by_user_id' => auth()->id(),
                    ]);
                }
            }
        });

        $classrooms = Classroom::whereIn('id', $validated['classroom_ids'] ?? [])
            ->select('id', 'name')
            ->get()
            ->toArray();

        return response()->json([
            'success' => true,
            'classrooms' => $classrooms,
        ]);
    }

    public function syncClassroom(Request $request, Course $course, CourseGroup $group)
    {
        $this->validateAccess($course);
        $this->validateGroup($course, $group);

        $validated = $request->validate([
            'dry_run' => 'required|boolean',
            'detach_member_ids' => 'nullable|array',
            'detach_member_ids.*' => 'integer',
        ]);

        $isDryRun = $validated['dry_run'];

        if ($isDryRun) {
            $result = $this->rosterService->syncPreview(auth()->user(), $course, $group);
            $result['success'] = true;
            $result['dry_run'] = true;

            return response()->json($result);
        } else {
            $detachIds = $validated['detach_member_ids'] ?? [];
            $result = $this->rosterService->syncApply(auth()->user(), $course, $group, $detachIds);
            $result['success'] = true;
            $result['dry_run'] = false;

            return response()->json($result);
        }
    }
}
