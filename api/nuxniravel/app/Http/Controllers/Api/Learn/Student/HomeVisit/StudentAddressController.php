<?php

namespace App\Http\Controllers\Api\Learn\Student\HomeVisit;

use App\Http\Controllers\Controller;
use App\Models\StudentAddress;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentAddressController extends Controller
{
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

            // เพิ่ม student_id จาก route parameter
            $validated['student_id'] = $studentId;

            // หากเป็นที่อยู่ปัจจุบัน ให้เปลี่ยนที่อยู่อื่นให้ไม่ใช่ปัจจุบัน
            if (isset($validated['is_current']) && $validated['is_current']) {
                StudentAddress::where('student_id', $studentId)
                    ->update(['is_current' => false]);
            }

            $address = StudentAddress::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'เพิ่มข้อมูลที่อยู่เรียบร้อยแล้ว',
                'data' => $address
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูลที่อยู่'
            ], 500);
        }
    }

    public function update(Request $request, $studentId, $id)
    {
        try {
            $address = StudentAddress::findOrFail($id);
            
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

            // ตรวจสอบว่าที่อยู่นี้เป็นของนักเรียนที่ถูกต้องหรือไม่
            if ((int)$address->student_id !== (int)$studentId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ไม่สามารถแก้ไขข้อมูลที่อยู่ของนักเรียนคนอื่นได้'
                ], 403);
            }

            // เพิ่ม student_id ให้กับ validated data
            $validated['student_id'] = $studentId;

            // หากเป็นที่อยู่ปัจจุบัน ให้เปลี่ยนที่อยู่อื่นให้ไม่ใช่ปัจจุบัน
            if (isset($validated['is_current']) && $validated['is_current']) {
                StudentAddress::where('student_id', $address->student_id)
                    ->where('id', '!=', $id)
                    ->update(['is_current' => false]);
            }

            $address->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'แก้ไขข้อมูลที่อยู่เรียบร้อยแล้ว',
                'data' => $address->fresh()
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลที่อยู่ที่ต้องการแก้ไข'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูลที่อยู่'
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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลที่อยู่ที่ต้องการลบ'
            ], 404);
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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลที่อยู่'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการตั้งที่อยู่ปัจจุบัน'
            ], 500);
        }
    }
}
