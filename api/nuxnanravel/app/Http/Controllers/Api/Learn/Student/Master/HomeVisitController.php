<?php

namespace App\Http\Controllers\Api\Learn\Student\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreHomeVisitRequest;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\ClassroomMember;
use App\Models\Student;
use App\Models\StudentHomeVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomeVisitController extends Controller
{
    public function index(Academy $academy, Student $student): JsonResponse
    {
        $this->ensureStudentScope($academy, $student);
        $accessLevel = $this->accessLevel($academy, $student);
        abort_unless($accessLevel, 403, 'ไม่มีสิทธิ์ดูข้อมูลการเยี่ยมบ้าน');

        $visits = $student->homeVisits()
            ->with(['zone:id,zone_name', 'creator:id,name'])
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->paginate(20);

        $canSeeObservations = in_array($accessLevel, ['admin', 'homeroom', 'teacher'], true);
        $visits->through(fn (StudentHomeVisit $visit) => $this->serializeVisit($visit, $canSeeObservations));

        return response()->json(['success' => true, 'data' => $visits]);
    }

    public function store(StoreHomeVisitRequest $request, Academy $academy, Student $student): JsonResponse
    {
        $this->ensureStudentScope($academy, $student);
        abort_unless($this->isStaff($academy), 403, 'เฉพาะเจ้าหน้าที่โรงเรียนเท่านั้นที่บันทึกการเยี่ยมบ้านได้');

        $visit = $student->homeVisits()->create([
            ...$request->validated(),
            'academy_id' => $academy->id,
            'created_by' => auth('api')->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการเยี่ยมบ้านเรียบร้อยแล้ว',
            'data' => $this->serializeVisit($visit->load(['zone:id,zone_name', 'creator:id,name']), true),
        ], 201);
    }

    public function show(Academy $academy, Student $student, StudentHomeVisit $homeVisit): JsonResponse
    {
        $this->ensureVisitScope($academy, $student, $homeVisit);
        $accessLevel = $this->accessLevel($academy, $student);
        abort_unless($accessLevel, 403, 'ไม่มีสิทธิ์ดูข้อมูลการเยี่ยมบ้าน');

        return response()->json([
            'success' => true,
            'data' => $this->serializeVisit(
                $homeVisit->load(['zone:id,zone_name', 'creator:id,name']),
                in_array($accessLevel, ['admin', 'homeroom', 'teacher'], true),
            ),
        ]);
    }

    public function update(StoreHomeVisitRequest $request, Academy $academy, Student $student, StudentHomeVisit $homeVisit): JsonResponse
    {
        $this->ensureVisitScope($academy, $student, $homeVisit);
        abort_unless($this->isStaff($academy), 403, 'เฉพาะเจ้าหน้าที่โรงเรียนเท่านั้นที่แก้ไขการเยี่ยมบ้านได้');

        $homeVisit->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'แก้ไขการเยี่ยมบ้านเรียบร้อยแล้ว',
            'data' => $this->serializeVisit($homeVisit->fresh()->load(['zone:id,zone_name', 'creator:id,name']), true),
        ]);
    }

    public function destroy(Academy $academy, Student $student, StudentHomeVisit $homeVisit): JsonResponse
    {
        $this->ensureVisitScope($academy, $student, $homeVisit);
        abort_unless($this->canManage($academy, $student), 403, 'ไม่มีสิทธิ์ลบการเยี่ยมบ้าน');

        $homeVisit->delete();

        return response()->json(['success' => true, 'message' => 'ลบการเยี่ยมบ้านเรียบร้อยแล้ว']);
    }

    public function updateStatus(Request $request, Academy $academy, Student $student, StudentHomeVisit $homeVisit): JsonResponse
    {
        $this->ensureVisitScope($academy, $student, $homeVisit);
        abort_unless($this->canManage($academy, $student), 403, 'ไม่มีสิทธิ์เปลี่ยนสถานะการเยี่ยมบ้าน');

        $validated = $request->validate([
            'visit_status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])],
        ]);
        $homeVisit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'เปลี่ยนสถานะเรียบร้อยแล้ว',
            'data' => $this->serializeVisit($homeVisit->fresh(), true),
        ]);
    }

    private function ensureStudentScope(Academy $academy, Student $student): void
    {
        abort_unless((int) $student->academy_id === (int) $academy->id, 404);
    }

    private function ensureVisitScope(Academy $academy, Student $student, StudentHomeVisit $homeVisit): void
    {
        $this->ensureStudentScope($academy, $student);
        abort_unless(
            (int) $homeVisit->student_id === (int) $student->id
            && (int) $homeVisit->academy_id === (int) $academy->id,
            404,
        );
    }

    private function accessLevel(Academy $academy, Student $student): ?string
    {
        $user = auth('api')->user();
        if (! $user) {
            return null;
        }
        if ((int) $student->user_id === (int) $user->id) {
            return 'self';
        }

        $member = AcademyMember::query()
            ->where('academy_id', $academy->id)
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->first();
        if ($member && in_array($member->role, ['owner', 'admin', 'director'], true)) {
            return 'admin';
        }
        if ($member && in_array($member->role, ['teacher', 'co_teacher'], true)) {
            return $this->isHomeroom($student, $user->id) ? 'homeroom' : 'teacher';
        }

        // อ่านจากตารางเดิมจนกว่า G-S4 (write path) จะเสร็จ — ถ้าเปลี่ยนก่อน ผู้ปกครองที่เพิ่มใหม่จะถูกล็อกไม่ให้เข้า
        $isGuardian = $student->guardians()
            ->whereNotNull('citizen_id')
            ->where('citizen_id', $user->citizen_id ?? '__none__')
            ->exists();

        return $isGuardian ? 'parent' : null;
    }

    private function isStaff(Academy $academy): bool
    {
        $userId = auth('api')->id();

        return $userId && AcademyMember::query()
            ->where('academy_id', $academy->id)
            ->where('user_id', $userId)
            ->where('status', 1)
            ->whereIn('role', ['owner', 'admin', 'director', 'teacher', 'co_teacher'])
            ->exists();
    }

    private function canManage(Academy $academy, Student $student): bool
    {
        $userId = auth('api')->id();
        if (! $userId) {
            return false;
        }

        $isAdmin = AcademyMember::query()
            ->where('academy_id', $academy->id)
            ->where('user_id', $userId)
            ->where('status', 1)
            ->whereIn('role', ['owner', 'admin', 'director'])
            ->exists();

        return $isAdmin || $this->isHomeroom($student, $userId);
    }

    private function isHomeroom(Student $student, int $userId): bool
    {
        return ClassroomMember::query()
            ->whereHas('classroom.classroomStudents', fn ($query) => $query
                ->where('student_id', $student->id)
                ->where('status', 'active'))
            ->where('user_id', $userId)
            ->where('role', 'teacher')
            ->where('is_active', true)
            ->exists();
    }

    private function serializeVisit(StudentHomeVisit $visit, bool $includeObservations): array
    {
        $publicStatus = match ($visit->visit_status) {
            'pending', 'rescheduled', 'in-progress' => 'scheduled',
            default => $visit->visit_status,
        };

        return [
            'id' => $visit->id,
            'visit_date' => $visit->visit_date?->toDateString(),
            'visit_time' => $visit->visit_time?->format('H:i'),
            'visitor_name' => $visit->visitor_name,
            'visitor_position' => $visit->visitor_position,
            'visit_status' => $publicStatus,
            'zone_id' => $visit->zone_id,
            'zone' => $visit->relationLoaded('zone') ? $visit->zone : null,
            'observations' => $includeObservations ? $visit->observations : null,
            'recommendations' => $visit->recommendations,
            'follow_up' => $visit->follow_up,
            'next_visit' => $visit->next_visit?->toDateString(),
            'created_by' => $visit->created_by,
            'creator' => $visit->relationLoaded('creator') ? $visit->creator : null,
            'created_at' => $visit->created_at?->toIso8601String(),
            'updated_at' => $visit->updated_at?->toIso8601String(),
        ];
    }
}
