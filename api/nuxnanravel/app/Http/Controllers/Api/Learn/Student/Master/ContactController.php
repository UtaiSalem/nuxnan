<?php

namespace App\Http\Controllers\Api\Learn\Student\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateContactRequest;
use App\Models\Academy;
use App\Models\Student;
use App\Models\StudentContact;
use App\Traits\HandlesStudentUpdates;

class ContactController extends Controller
{
    use HandlesStudentUpdates;

    public function index(Academy $academy, Student $student)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        $this->authorize('update', $student);

        try {
            $contacts = StudentContact::where('student_id', $student->id)
                ->orderBy('is_primary', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $contacts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลการติดต่อ',
            ], 500);
        }
    }

    public function store(UpdateContactRequest $request, Academy $academy, Student $student)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        $this->authorize('update', $student);

        try {
            $validated = $request->validated();

            // Check if creation needs approval
            $changeRequest = $this->applyUpdate($student, 'StudentContact', null, 'contact.create', $validated);
            if ($changeRequest) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'ส่งคำขอเพิ่มข้อมูลการติดต่อแล้ว รอการอนุมัติ',
                    'needs_approval' => true,
                ]);
            }

            // หากเป็นการติดต่อหลัก ให้เปลี่ยนการติดต่ออื่นให้ไม่ใช่หลัก
            if (isset($validated['is_primary']) && $validated['is_primary']) {
                StudentContact::where('student_id', $student->id)
                    ->update(['is_primary' => false]);
            }

            $validated['student_id'] = $student->id;
            $contact = StudentContact::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'เพิ่มข้อมูลการติดต่อเรียบร้อยแล้ว',
                'data' => $contact,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูลการติดต่อ: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateContactRequest $request, Academy $academy, Student $student, StudentContact $contact)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        if ($contact->student_id !== $student->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลการติดต่อไม่ตรงกับนักเรียน',
            ], 403);
        }

        $this->authorize('update', $student);

        try {
            $validated = $request->validated();

            $needsApproval = false;
            $directUpdates = [];

            foreach ($validated as $field => $value) {
                if ($value === $contact->$field) {
                    continue;
                }

                $changeRequest = $this->applyUpdate($student, 'StudentContact', $contact->id, "contact.$field", $value, $contact->$field);
                if ($changeRequest) {
                    $needsApproval = true;
                } else {
                    $directUpdates[$field] = $value;
                }
            }

            if (! empty($directUpdates)) {
                // หากเป็นการติดต่อหลัก ให้เปลี่ยนการติดต่ออื่นให้ไม่ใช่หลัก
                if (isset($directUpdates['is_primary']) && $directUpdates['is_primary']) {
                    StudentContact::where('student_id', $contact->student_id)
                        ->where('id', '!=', $contact->id)
                        ->update(['is_primary' => false]);
                }
                $contact->update($directUpdates);
            }

            if ($needsApproval) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'ส่งคำขอแก้ไขข้อมูลการติดต่อแล้ว รอการอนุมัติ ส่วนข้อมูลที่ไม่ต้องอนุมัติถูกอัปเดตแล้ว',
                    'needs_approval' => true,
                    'data' => $contact->fresh(),
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'แก้ไขข้อมูลการติดต่อเรียบร้อยแล้ว',
                'data' => $contact->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูลการติดต่อ: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Academy $academy, Student $student, StudentContact $contact)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        if ($contact->student_id !== $student->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลการติดต่อไม่ตรงกับนักเรียน',
            ], 403);
        }

        $this->authorize('update', $student);

        try {
            $contact->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'ลบข้อมูลการติดต่อเรียบร้อยแล้ว',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการลบข้อมูลการติดต่อ',
            ], 500);
        }
    }

    public function setPrimary(Academy $academy, Student $student, StudentContact $contact)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        if ($contact->student_id !== $student->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลการติดต่อไม่ตรงกับนักเรียน',
            ], 403);
        }

        $this->authorize('update', $student);

        try {
            // เปลี่ยนการติดต่ออื่นให้ไม่ใช่หลัก
            StudentContact::where('student_id', $student->id)
                ->update(['is_primary' => false]);

            // ตั้งการติดต่อนี้เป็นหลัก
            $contact->update(['is_primary' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'ตั้งค่าเป็นการติดต่อหลักเรียบร้อยแล้ว',
                'data' => $contact->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการตั้งค่า',
            ], 500);
        }
    }
}
