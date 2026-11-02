<?php

namespace App\Http\Controllers\Api\Learn\Course\info;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learn\Course\UpdateCourseAffiliationRequest;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Models\Academy;
use App\Models\Course;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * ผูก/ถอนรายวิชาออกจากสังกัดโรงเรียน (courses.academy_id)
 * ดูสเปค .agents/specs/course-academy-affiliation.md
 */
class CourseAffiliationController extends Controller
{
    public function __construct(private AuditLogService $auditLog) {}

    /**
     * GET /api/me/course-host-academies — โรงเรียนที่ผู้ใช้เอารายวิชามาไว้ใต้สังกัดได้
     */
    public function hostAcademies(): JsonResponse
    {
        $academies = Academy::query()
            ->hostableBy(auth()->user())
            ->orderBy('name')
            ->get(['id', 'name', 'name_en', 'logo']);

        return response()->json([
            'success' => true,
            'academies' => $academies->map(fn (Academy $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'name_en' => $a->name_en,
                'logo_url' => $a->logo_url,
            ])->values(),
        ]);
    }

    /**
     * PATCH /api/courses/{course}/academy — ผูกรายวิชาส่วนตัวเข้าสังกัด
     */
    public function update(UpdateCourseAffiliationRequest $request, Course $course): JsonResponse
    {
        $user = auth()->user();

        if (! $course->canAttachToAcademy($user)) {
            return response()->json(['success' => false, 'message' => 'คุณไม่มีสิทธิ์จัดการสังกัดของรายวิชานี้'], 403);
        }

        if ($course->academy_id !== null) {
            return response()->json(['success' => false, 'message' => 'รายวิชานี้สังกัดโรงเรียนอยู่แล้ว ต้องถอนออกจากสังกัดเดิมก่อน'], 422);
        }

        $academy = Academy::findOrFail($request->validated('academy_id'));

        if (! $academy->canHostCourse($user)) {
            return response()->json(['success' => false, 'message' => 'คุณต้องเป็นครูหรือผู้ดูแลของโรงเรียนนี้ จึงจะเพิ่มรายวิชาเข้าสังกัดได้'], 403);
        }

        DB::transaction(function () use ($course, $academy) {
            $course->update(['academy_id' => $academy->id]);
            $academy->update(['courses_offered' => $academy->courses()->count()]);
            $this->auditLog->log('course_academy_attached', $course, ['academy_id' => null], ['academy_id' => $academy->id], 'courses');
        });

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มรายวิชาเข้าสังกัดโรงเรียนเรียบร้อยแล้ว',
            'course' => new CourseResource($course->fresh()->load(['user', 'academy'])),
        ]);
    }

    /**
     * DELETE /api/courses/{course}/academy — ถอนรายวิชาออกจากสังกัด
     */
    public function destroy(Course $course): JsonResponse
    {
        $user = auth()->user();

        if ($course->academy_id === null) {
            return response()->json(['success' => false, 'message' => 'รายวิชานี้ไม่ได้สังกัดโรงเรียน'], 422);
        }

        if (! $course->canDetachFromAcademy($user)) {
            return response()->json(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ถอนรายวิชานี้ออกจากสังกัด'], 403);
        }

        // อยู่ในหลักสูตรของโรงเรียน → ต้องถอดออกจากหลักสูตรก่อน (กันหลักสูตรอ้างรายวิชาที่ไม่ใช่ของโรงเรียนแล้ว)
        if (DB::table('curriculum_courses')->where('course_id', $course->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'รายวิชานี้อยู่ในหลักสูตรของโรงเรียน กรุณานำออกจากหลักสูตรก่อน'], 422);
        }

        $academy = $course->academy;
        $oldAcademyId = $course->academy_id;

        DB::transaction(function () use ($course, $academy, $oldAcademyId) {
            // log ก่อน update เพื่อให้ AuditLogService ยังเห็น academy_id เดิมบน entity (ประวัติไปอยู่กับโรงเรียนเดิม)
            $this->auditLog->log('course_academy_detached', $course, ['academy_id' => $oldAcademyId], ['academy_id' => null], 'courses');
            $course->update(['academy_id' => null]);
            if ($academy) {
                $academy->update(['courses_offered' => $academy->courses()->count()]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'ถอนรายวิชาออกจากสังกัดเรียบร้อยแล้ว',
            'course' => new CourseResource($course->fresh()->load(['user', 'academy'])),
        ]);
    }
}
