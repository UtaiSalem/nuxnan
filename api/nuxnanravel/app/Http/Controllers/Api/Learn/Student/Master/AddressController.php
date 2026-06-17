<?php

namespace App\Http\Controllers\Api\Learn\Student\Master;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Traits\HandlesStudentUpdates;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    use HandlesStudentUpdates;

    public function index($studentId)
    {
        try {
            $addresses = StudentAddress::where('student_id', $studentId)
                ->orderBy('is_current', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $addresses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลที่อยู่'
            ], 500);
        }
    }

    public function store(Request $request, $studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            $validated = $request->validate([
                'address_type' => ['required', Rule::in(['current', 'permanent', 'temporary'])],
                'house_number' => 'required|string|max:50',
                'village_number' => 'nullable|string|max:20',
                'village_name' => 'nullable|string|max:100',
                'alley' => 'nullable|string|max:100',
                'road' => 'nullable|string|max:100',
                'subdistrict' => 'required|string|max:100',
                'district' => 'required|string|max:100',
                'province' => 'required|string|max:100',
                'postal_code' => 'nullable|string|max:10',
                'is_current' => 'boolean'
            ]);

            // Check if creation needs approval (using a generic 'address' field check)
            $changeRequest = $this->applyUpdate($student, 'StudentAddress', null, 'address.create', $validated);
            if ($changeRequest) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'ส่งคำขอเพิ่มข้อมูลที่อยู่แล้ว รอการอนุมัติ',
                    'needs_approval' => true
                ]);
            }

            // หากเป็นที่อยู่ปัจจุบัน ให้เปลี่ยนที่อยู่อื่นให้ไม่ใช่ปัจจุบัน
            if (isset($validated['is_current']) && $validated['is_current']) {
                StudentAddress::where('student_id', $studentId)
                    ->update(['is_current' => false]);
            }

            $validated['student_id'] = $studentId;
            $validated['academy_id'] = $student->academy_id;
            $address = StudentAddress::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'เพิ่มข้อมูลที่อยู่เรียบร้อยแล้ว',
                'data' => $address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูลที่อยู่: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $studentId, $id)
    {
        try {
            $student = Student::findOrFail($studentId);
            $address = StudentAddress::where('student_id', $studentId)->findOrFail($id);
            
            $validated = $request->validate([
                'address_type' => ['nullable', Rule::in(['current', 'permanent', 'temporary'])],
                'house_number' => 'nullable|string|max:50',
                'village_number' => 'nullable|string|max:20',
                'village_name' => 'nullable|string|max:100',
                'alley' => 'nullable|string|max:100',
                'road' => 'nullable|string|max:100',
                'subdistrict' => 'nullable|string|max:100',
                'district' => 'nullable|string|max:100',
                'province' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:10',
                'is_current' => 'nullable|boolean'
            ]);

            $needsApproval = false;
            $directUpdates = [];

            foreach ($validated as $field => $value) {
                if ($value === $address->$field) continue;
                
                $changeRequest = $this->applyUpdate($student, 'StudentAddress', $address->id, "address.$field", $value, $address->$field);
                if ($changeRequest) {
                    $needsApproval = true;
                } else {
                    $directUpdates[$field] = $value;
                }
            }

            if (!empty($directUpdates)) {
                // หากเป็นที่อยู่ปัจจุบัน ให้เปลี่ยนที่อยู่อื่นให้ไม่ใช่ปัจจุบัน
                if (isset($directUpdates['is_current']) && $directUpdates['is_current']) {
                    StudentAddress::where('student_id', $address->student_id)
                        ->where('id', '!=', $id)
                        ->update(['is_current' => false]);
                }
                $address->update($directUpdates);
            }

            if ($needsApproval) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'ส่งคำขอแก้ไขบางข้อมูลที่อยู่แล้ว รอการอนุมัติ ส่วนข้อมูลที่ไม่ต้องอนุมัติถูกอัปเดตแล้ว',
                    'needs_approval' => true,
                    'data' => $address->fresh()
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'แก้ไขข้อมูลที่อยู่เรียบร้อยแล้ว',
                'data' => $address->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูลที่อยู่: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $address = StudentAddress::findOrFail($id);
            $address->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'ลบข้อมูลที่อยู่เรียบร้อยแล้ว'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการลบข้อมูลที่อยู่'
            ], 500);
        }
    }

    public function setCurrent($id)
    {
        try {
            $address = StudentAddress::findOrFail($id);

            // เปลี่ยนที่อยู่อื่นให้ไม่ใช่ปัจจุบัน
            StudentAddress::where('student_id', $address->student_id)
                ->update(['is_current' => false]);

            // ตั้งที่อยู่นี้เป็นปัจจุบัน
            $address->update(['is_current' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'ตั้งเป็นที่อยู่ปัจจุบันเรียบร้อยแล้ว',
                'data' => $address->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการตั้งที่อยู่ปัจจุบัน'
            ], 500);
        }
    }
}
