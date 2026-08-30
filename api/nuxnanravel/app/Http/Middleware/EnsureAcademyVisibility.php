<?php

namespace App\Http\Middleware;

use App\Models\Academy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * บังคับใช้สวิตช์การมองเห็นของโรงเรียน (privacy, show_member_list, show_course_list)
 *
 * ใช้เป็น academy.visibility:content|members|courses
 *
 * ด่านนี้จะ "ไม่ทำอะไรเลย" กับโรงเรียนที่ privacy=public และสวิตช์เปิดอยู่ —
 * มันจะเริ่มปฏิเสธก็ต่อเมื่อผู้ดูแลโรงเรียนตั้งค่าให้ปิดจริง ๆ เท่านั้น
 * ตรรกะทั้งหมดอยู่บนโมเดล Academy (canViewContent / canViewMemberList / canViewCourseList)
 * ห้ามเขียนเงื่อนไขซ้ำในนี้
 * นอกจากสวิตช์แล้ว ด่านนี้ยังกันโรงเรียนที่ถูกเก็บถาวร (SET-S2) ด้วย code `academy_archived`
 */
class EnsureAcademyVisibility
{
    public function handle(Request $request, Closure $next, string $aspect = 'content'): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $academy = $request->route('academy');

        if (! $academy instanceof Academy) {
            $academy = Academy::find($academy);
        }

        if (! $academy) {
            return response()->json(['success' => false, 'message' => 'Academy not found'], 404);
        }

        // SET-S2 — โรงเรียนที่ถูกเก็บถาวรต้องตอบด้วย code ของตัวเอง ไม่ใช่ academy_private
        // (frontend ต้องแยกสองสถานะนี้ออกจากกันได้ ข้อความที่ผู้ใช้เห็นคนละเรื่องกัน)
        if ($academy->isArchived() && ! $academy->canManageArchive($user)) {
            return response()->json([
                'success' => false,
                'code' => 'academy_archived',
                'message' => 'โรงเรียนนี้ถูกเก็บถาวรแล้ว',
            ], 403);
        }

        [$allowed, $code, $message] = match ($aspect) {
            'members' => [
                $academy->canViewMemberList($user),
                'member_list_hidden',
                'โรงเรียนนี้ไม่เปิดเผยรายชื่อสมาชิกให้บุคคลภายนอก',
            ],
            'courses' => [
                $academy->canViewCourseList($user),
                'course_list_hidden',
                'โรงเรียนนี้ไม่เปิดเผยรายการคอร์สให้บุคคลภายนอก',
            ],
            default => [
                $academy->canViewContent($user),
                'academy_private',
                'โรงเรียนนี้เป็นแบบส่วนตัว เฉพาะสมาชิกเท่านั้นที่เข้าถึงได้',
            ],
        };

        if (! $allowed) {
            return response()->json([
                'success' => false,
                'code' => $code,
                'message' => $message,
            ], 403);
        }

        return $next($request);
    }
}
