<?php

namespace App\Http\Middleware;

use App\Models\Academy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorizes academy permissions from the academy role, then explicit group grants.
 *
 * ลำดับการปล่อยผ่านทั้งหมดอยู่ที่ `Academy::userCan()` — **ห้ามเขียนซ้ำที่นี่**
 * ด่านนี้เหลือหน้าที่แค่แปลงผลเป็น HTTP response (401/404/403 พร้อมข้อความที่ถูกตัว)
 *
 * ไม่ส่งคีย์มาเลย (`academy.permission`) = ขอแค่ "เป็นสมาชิกที่อนุมัติแล้ว"
 *
 * Group-derived permissions are not data-scoped until D-S4 is complete. Before
 * enabling a permission for a group, be aware that its members can see or
 * modify data across the whole academy.
 */
class CheckAcademyPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $academy = $request->route('academy');

        if (! $academy instanceof Academy) {
            $academy = Academy::find($academy);
        }

        if (! $academy) {
            return response()->json(['success' => false, 'message' => 'Academy not found'], 404);
        }

        if ($academy->userCan($user, ...$permissions)) {
            return $next($request);
        }

        // แยกสองสาเหตุออกจากกัน: "ไม่ใช่สมาชิก" กับ "เป็นสมาชิกแต่สิทธิ์ไม่พอ"
        // frontend ใช้ข้อความนี้ตัดสินใจว่าจะชวนเข้าร่วมหรือบอกให้ติดต่อผู้ดูแล
        if (! $academy->isApprovedMember($user)) {
            return response()->json(['success' => false, 'message' => 'Not a member of this academy'], 403);
        }

        return response()->json(['success' => false, 'message' => 'Insufficient permissions'], 403);
    }
}
