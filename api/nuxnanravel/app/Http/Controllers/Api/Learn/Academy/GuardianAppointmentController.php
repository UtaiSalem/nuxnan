<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentGuardianLink;
use App\Services\GuardianAccessService;
use App\Services\GuardianAuditLogger;
use App\Services\GuardianWriteService;
use App\Support\GuardianNameNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuardianAppointmentController extends Controller
{
    private GuardianWriteService $writeService;

    public function __construct(GuardianWriteService $writeService)
    {
        $this->writeService = $writeService;
    }

    public function search(Request $request, Academy $academy)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $guardians = Guardian::query()
            ->where('academy_id', $academy->id)
            ->where(fn ($q) => $q->where('first_name', 'LIKE', "%{$validated['q']}%")
                ->orWhere('last_name', 'LIKE', "%{$validated['q']}%"))
            ->orderBy('first_name')
            ->limit((int) ($validated['per_page'] ?? 20))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $guardians->map(fn ($g) => [
                'id' => $g->id,
                'title_prefix' => $g->title_prefix,
                'first_name' => $g->first_name,
                'last_name' => $g->last_name,
                'full_name' => trim(($g->title_prefix ? $g->title_prefix.' ' : '').$g->first_name.' '.$g->last_name),
                'children_count' => (int) DB::table('student_guardian_links')->where('guardian_id', $g->id)->count(),
            ])->values(),
        ]);
    }

    public function match(Request $request, Academy $academy, Student $student)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้'], 403);
        }

        $this->authorize('appointGuardians', $student);

        $validated = $request->validate([
            'citizen_id' => ['required', 'string', 'regex:/^\d{13}$/'],
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
        ]);

        $person = Guardian::where('academy_id', $academy->id)
            ->where('citizen_id', $validated['citizen_id'])
            ->get()
            ->first(fn ($p) => GuardianNameNormalizer::normalize($p->first_name) === GuardianNameNormalizer::normalize($validated['first_name'])
                && GuardianNameNormalizer::normalize($p->last_name) === GuardianNameNormalizer::normalize($validated['last_name']));

        if (! $person) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $person->id,
                'full_name' => trim(($person->title_prefix ? $person->title_prefix.' ' : '').$person->first_name.' '.$person->last_name),
                'children_count' => DB::table('student_guardian_links')->where('guardian_id', $person->id)->count(),
                'already_linked' => DB::table('student_guardian_links')
                    ->where('guardian_id', $person->id)->where('student_id', $student->id)->exists(),
            ],
        ]);
    }

    public function appoint(Request $request, Academy $academy, Student $student)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้'], 403);
        }

        $this->authorize('appointGuardians', $student);

        $validated = $request->validate([
            'guardian_id' => 'required|integer|exists:guardians,id',
            'guardian_type' => 'nullable|string|max:50',
            'relationship' => 'nullable|string|max:50',
            'is_primary_contact' => 'nullable|boolean',
            'is_emergency_contact' => 'nullable|boolean',
        ]);

        $person = Guardian::find($validated['guardian_id']);
        if ($person->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ปกครองในโรงเรียนนี้'], 404);
        }

        $linkExists = DB::table('student_guardian_links')
            ->where('student_id', $student->id)
            ->where('guardian_id', $person->id)
            ->exists();

        if ($linkExists) {
            return response()->json(['success' => false, 'message' => 'ผู้ปกครองคนนี้ถูกแต่งตั้งให้นักเรียนคนนี้อยู่แล้ว'], 409);
        }

        $actorRole = app(GuardianAccessService::class)->actorRole(auth()->user(), $student);

        $linkData = array_intersect_key($validated, array_flip(['guardian_type', 'relationship', 'is_primary_contact', 'is_emergency_contact']));

        DB::transaction(function () use ($student, $person, $linkData, $actorRole) {
            if (! empty($linkData['is_primary_contact'])) {
                DB::table('student_guardian_links')->where('student_id', $student->id)->update(['is_primary_contact' => false]);
                DB::table('student_guardians')->where('student_id', $student->id)->update(['is_primary_contact' => false]);
            }

            $this->writeService->appoint($student, $person, $linkData, $actorRole, auth()->id());
        });

        app(GuardianAuditLogger::class)->appointed($student, $person, $actorRole, $linkData);

        return response()->json([
            'success' => true,
            'message' => 'แต่งตั้งผู้ปกครองสำเร็จ',
            'data' => [
                'guardian_id' => $person->id,
                'appointed_by_role' => $actorRole,
                'verified' => false,
            ],
        ], 201);
    }

    public function verify(Academy $academy, Student $student, StudentGuardianLink $link)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้'], 403);
        }

        $this->authorize('manageGuardians', $student);

        if ($student->user_id !== null && $student->user_id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'นักเรียนยืนยันการแต่งตั้งของตัวเองไม่ได้ ต้องให้ครูประจำชั้นหรือฝ่ายทะเบียนยืนยัน'], 403);
        }

        if ($link->student_id !== $student->id) {
            return response()->json(['success' => false, 'message' => 'ไม่พบการแต่งตั้งนี้ของนักเรียนคนนี้'], 404);
        }

        if (! $this->writeService->verify($link, auth()->id())) {
            return response()->json(['success' => false, 'message' => 'การแต่งตั้งนี้ถูกยืนยันแล้ว'], 409);
        }

        app(GuardianAuditLogger::class)->verified($student, $link->guardian);

        return response()->json([
            'success' => true,
            'message' => 'ยืนยันการแต่งตั้งผู้ปกครองแล้ว',
            'data' => [
                'verified_at' => $link->fresh()->verified_at,
            ],
        ], 200);
    }
}
