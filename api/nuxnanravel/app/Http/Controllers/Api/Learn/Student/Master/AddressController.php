<?php

namespace App\Http\Controllers\Api\Learn\Student\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateAddressRequest;
use App\Models\Academy;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Traits\HandlesStudentUpdates;

class AddressController extends Controller
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
            $addresses = StudentAddress::where('student_id', $student->id)
                ->orderBy('is_current', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $addresses,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลที่อยู่',
            ], 500);
        }
    }

    public function store(UpdateAddressRequest $request, Academy $academy, Student $student)
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
            $changeRequest = $this->applyUpdate($student, 'StudentAddress', null, 'address.create', $validated);
            if ($changeRequest) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'ส่งคำขอเพิ่มข้อมูลที่อยู่แล้ว รอการอนุมัติ',
                    'needs_approval' => true,
                ]);
            }

            // หากเป็นที่อยู่ปัจจุบัน ให้เปลี่ยนที่อยู่อื่นให้ไม่ใช่ปัจจุบัน
            if (isset($validated['is_current']) && $validated['is_current']) {
                StudentAddress::where('student_id', $student->id)
                    ->update(['is_current' => false]);
            }

            $validated['student_id'] = $student->id;
            $validated['academy_id'] = $academy->id;
            $address = StudentAddress::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'เพิ่มข้อมูลที่อยู่เรียบร้อยแล้ว',
                'data' => $address,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูลที่อยู่: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateAddressRequest $request, Academy $academy, Student $student, StudentAddress $address)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        if ($address->student_id !== $student->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลที่อยู่ไม่ตรงกับนักเรียน',
            ], 403);
        }

        $this->authorize('update', $student);

        try {
            $validated = $request->validated();

            $needsApproval = false;
            $directUpdates = [];

            foreach ($validated as $field => $value) {
                if ($value === $address->$field) {
                    continue;
                }

                $changeRequest = $this->applyUpdate($student, 'StudentAddress', $address->id, "address.$field", $value, $address->$field);
                if ($changeRequest) {
                    $needsApproval = true;
                } else {
                    $directUpdates[$field] = $value;
                }
            }

            if (! empty($directUpdates)) {
                // หากเป็นที่อยู่ปัจจุบัน ให้เปลี่ยนที่อยู่อื่นให้ไม่ใช่ปัจจุบัน
                if (isset($directUpdates['is_current']) && $directUpdates['is_current']) {
                    StudentAddress::where('student_id', $address->student_id)
                        ->where('id', '!=', $address->id)
                        ->update(['is_current' => false]);
                }
                $address->update($directUpdates);
            }

            if ($needsApproval) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'ส่งคำขอแก้ไขข้อมูลที่อยู่แล้ว รอการอนุมัติ ส่วนข้อมูลที่ไม่ต้องอนุมัติถูกอัปเดตแล้ว',
                    'needs_approval' => true,
                    'data' => $address->fresh(),
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'แก้ไขข้อมูลที่อยู่เรียบร้อยแล้ว',
                'data' => $address->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูลที่อยู่: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Academy $academy, Student $student, StudentAddress $address)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        if ($address->student_id !== $student->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลที่อยู่ไม่ตรงกับนักเรียน',
            ], 403);
        }

        $this->authorize('update', $student);

        try {
            $address->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'ลบข้อมูลที่อยู่เรียบร้อยแล้ว',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการลบข้อมูลที่อยู่',
            ], 500);
        }
    }

    public function setCurrent(Academy $academy, Student $student, StudentAddress $address)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        if ($address->student_id !== $student->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลที่อยู่ไม่ตรงกับนักเรียน',
            ], 403);
        }

        $this->authorize('update', $student);

        try {
            // เปลี่ยนที่อยู่อื่นให้ไม่ใช่ปัจจุบัน
            StudentAddress::where('student_id', $student->id)
                ->update(['is_current' => false]);

            // ตั้งที่อยู่นี้เป็นปัจจุบัน
            $address->update(['is_current' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'ตั้งเป็นที่อยู่ปัจจุบันเรียบร้อยแล้ว',
                'data' => $address->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการตั้งที่อยู่ปัจจุบัน',
            ], 500);
        }
    }
}
