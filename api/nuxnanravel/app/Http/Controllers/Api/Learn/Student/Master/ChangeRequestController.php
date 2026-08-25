<?php

namespace App\Http\Controllers\Api\Learn\Student\Master;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Student;
use App\Models\StudentAcademicInfo;
use App\Models\StudentAddress;
use App\Models\StudentChangeRequest;
use App\Models\StudentContact;
use App\Models\StudentGuardianLink;
use App\Models\StudentHealthInfo;
use App\Services\GuardianWriteService;
use Illuminate\Http\Request;

class ChangeRequestController extends Controller
{
    /**
     * List change requests for a student in an academy.
     */
    public function index(Academy $academy, Student $student)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        $this->authorize('approveRequests', [Student::class, $academy->id]);

        $requests = StudentChangeRequest::with(['requester', 'approver'])
            ->where('academy_id', $academy->id)
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Approve a student change request.
     */
    public function approve(Academy $academy, Student $student, StudentChangeRequest $changeRequest)
    {
        if ($changeRequest->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลคำขอไม่อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        if ($changeRequest->student_id !== $student->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลคำขอไม่ตรงกับนักเรียนคนนี้',
            ], 403);
        }

        $this->authorize('approveRequests', [Student::class, $academy->id]);

        if ($changeRequest->status !== 'pending') {
            return response()->json(['success' => false, 'error' => 'คำขอนี้ถูกดำเนินการไปแล้ว'], 400);
        }

        // Whitelist allowed models to prevent dynamic class instantiation vulnerability
        $allowedModels = [
            'Student' => Student::class,
            'StudentAddress' => StudentAddress::class,
            'StudentContact' => StudentContact::class,
            'StudentGuardianLink' => StudentGuardianLink::class,
            'StudentHealthInfo' => StudentHealthInfo::class,
            'StudentAcademicInfo' => StudentAcademicInfo::class,
        ];

        if (! array_key_exists($changeRequest->model_type, $allowedModels)) {
            return response()->json(['success' => false, 'error' => 'ประเภทข้อมูลไม่ถูกต้อง'], 400);
        }

        $modelClass = $allowedModels[$changeRequest->model_type];
        $model = $modelClass::find($changeRequest->model_id);

        if ($model) {
            $field = str_replace(['address.', 'contact.', 'guardian.', 'health.', 'academic.'], '', $changeRequest->field);
            if ($model instanceof StudentGuardianLink) {
                app(GuardianWriteService::class)->update($model, [$field => $changeRequest->new_value]);
            } else {
                $model->update([$field => $changeRequest->new_value]);
            }
        }

        $changeRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อนุมัติคำขอสำเร็จแล้วและอัปเดตข้อมูลแล้ว',
        ]);
    }

    /**
     * Reject a student change request.
     */
    public function reject(Request $request, Academy $academy, Student $student, StudentChangeRequest $changeRequest)
    {
        if ($changeRequest->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลคำขอไม่อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        if ($changeRequest->student_id !== $student->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลคำขอไม่ตรงกับนักเรียนคนนี้',
            ], 403);
        }

        $this->authorize('approveRequests', [Student::class, $academy->id]);

        if ($changeRequest->status !== 'pending') {
            return response()->json(['success' => false, 'error' => 'คำขอนี้ถูกดำเนินการไปแล้ว'], 400);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $changeRequest->update([
            'status' => 'rejected',
            'reason' => $request->reason,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ปฏิเสธคำขอเรียบร้อยแล้ว',
        ]);
    }
}
