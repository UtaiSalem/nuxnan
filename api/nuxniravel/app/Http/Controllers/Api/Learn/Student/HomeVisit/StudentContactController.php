<?php

namespace App\Http\Controllers\Api\Learn\Student\HomeVisit;

use App\Http\Controllers\Controller;
use App\Models\StudentContact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentContactController extends Controller
{
    public function index($studentId)
    {
        try {
            $contacts = StudentContact::where('student_id', $studentId)
                ->orderBy('is_primary', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $contacts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลการติดต่อ'
            ], 500);
        }
    }

    public function store(Request $request, $studentId)
    {
        try {
            $validated = $request->validate([
                'contact_type' => ['required', Rule::in(['phone', 'email', 'line', 'facebook'])],
                'contact_value' => 'required|string|max:255',
                'is_primary' => 'boolean'
            ]);

            // เพิ่ม student_id จาก route parameter
            $validated['student_id'] = $studentId;

            // หากเป็นการติดต่อหลัก ให้เปลี่ยนการติดต่ออื่นให้ไม่ใช่หลัก
            if (isset($validated['is_primary']) && $validated['is_primary']) {
                StudentContact::where('student_id', $studentId)
                    ->update(['is_primary' => false]);
            }

            $contact = StudentContact::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'เพิ่มข้อมูลการติดต่อเรียบร้อยแล้ว',
                'data' => $contact
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
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูลการติดต่อ'
            ], 500);
        }
    }

    public function update(Request $request, $studentId, $id)
    {
        try {
            $contact = StudentContact::findOrFail($id);
            
            $validated = $request->validate([
                'contact_type' => ['required', Rule::in(['phone', 'email', 'line', 'facebook'])],
                'contact_value' => 'required|string|max:255',
                'is_primary' => 'boolean'
            ]);

            // ตรวจสอบว่าการติดต่อนี้เป็นของนักเรียนที่ถูกต้องหรือไม่
            if ((int)$contact->student_id !== (int)$studentId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ไม่สามารถแก้ไขข้อมูลการติดต่อของนักเรียนคนอื่นได้'
                ], 403);
            }

            // เพิ่ม student_id ให้กับ validated data
            $validated['student_id'] = $studentId;

            // หากเป็นการติดต่อหลัก ให้เปลี่ยนการติดต่ออื่นให้ไม่ใช่หลัก
            if (isset($validated['is_primary']) && $validated['is_primary']) {
                StudentContact::where('student_id', $contact->student_id)
                    ->where('id', '!=', $id)
                    ->update(['is_primary' => false]);
            }

            $contact->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'แก้ไขข้อมูลการติดต่อเรียบร้อยแล้ว',
                'data' => $contact->fresh()
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
                'message' => 'ไม่พบข้อมูลการติดต่อที่ต้องการแก้ไข'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูลการติดต่อ'
            ], 500);
        }
    }

    public function destroy($studentId, $id)
    {
        try {
            $contact = StudentContact::findOrFail($id);
            
            // ตรวจสอบว่าการติดต่อนี้เป็นของนักเรียนที่ถูกต้องหรือไม่
            if ((int)$contact->student_id !== (int)$studentId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ไม่สามารถลบข้อมูลการติดต่อของนักเรียนคนอื่นได้'
                ], 403);
            }
            
            $contact->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'ลบข้อมูลการติดต่อเรียบร้อยแล้ว'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลการติดต่อที่ต้องการลบ'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการลบข้อมูลการติดต่อ'
            ], 500);
        }
    }

    public function setPrimary(Request $request, $studentId, $id)
    {
        try {
            $contact = StudentContact::findOrFail($id);
            
            // ตรวจสอบว่าการติดต่อนี้เป็นของนักเรียนที่ถูกต้องหรือไม่
            if ((int)$contact->student_id !== (int)$studentId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ไม่สามารถตั้งค่าข้อมูลการติดต่อของนักเรียนคนอื่นได้'
                ], 403);
            }

            // เปลี่ยนการติดต่ออื่นให้ไม่ใช่หลัก
            StudentContact::where('student_id', $studentId)
                ->update(['is_primary' => false]);

            // ตั้งการติดต่อนี้เป็นหลัก
            $contact->update(['is_primary' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'ตั้งค่าเป็นการติดต่อหลักเรียบร้อยแล้ว',
                'data' => $contact->fresh()
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลการติดต่อที่ต้องการ'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการตั้งค่า'
            ], 500);
        }
    }
}
