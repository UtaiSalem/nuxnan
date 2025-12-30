<?php

namespace App\Http\Controllers\Api\Learn\Student\HomeVisit;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentHealthInfo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StudentHealthController extends Controller
{
    /**
     * Show student health information
     */
    public function show(Student $student): JsonResponse
    {
        try {
            $healthInfo = StudentHealthInfo::where('student_id', $student->id)->first();
            
            if ($healthInfo) {
                return response()->json([
                    'status' => 'success',
                    'data' => $healthInfo
                ]);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => null,
                'message' => 'ยังไม่มีข้อมูลสุขภาพ'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error showing student health info: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการโหลดข้อมูลสุขภาพ'
            ], 500);
        }
    }

    /**
     * Store new health information
     */
    public function store(Request $request, Student $student): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'height' => 'nullable|numeric|between:0,300',
                'weight' => 'nullable|numeric|between:0,300',
                'allergies' => 'nullable|string|max:1000',
                'chronic_diseases' => 'nullable|string|max:1000',
                'medications' => 'nullable|string|max:1000',
                'blood_type' => 'nullable|string|max:10',
                'rh_factor' => 'nullable|string|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if health info already exists
            $existingHealth = StudentHealthInfo::where('student_id', $student->id)->first();
            if ($existingHealth) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ข้อมูลสุขภาพมีอยู่แล้ว กรุณาใช้การอัปเดต'
                ], 409);
            }

            $healthData = [
                'student_id' => $student->id,
                'height_cm' => $request->height,
                'weight_kg' => $request->weight,
                'allergies' => $request->allergies,
                'chronic_diseases' => $request->chronic_diseases,
                'medications' => $request->medications,
                'blood_type' => $request->blood_type,
                'rh_factor' => $request->rh_factor
            ];

            $health = StudentHealthInfo::create($healthData);

            return response()->json([
                'status' => 'success',
                'message' => 'บันทึกข้อมูลสุขภาพเรียบร้อยแล้ว',
                'data' => $health
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error storing student health info: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูลสุขภาพ'
            ], 500);
        }
    }

    /**
     * Update existing health information
     */
    public function update(Request $request, Student $student, StudentHealthInfo $health): JsonResponse
    {
        try {
            // Verify the health record belongs to this student
            if ($health->student_id !== $student->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ไม่มีสิทธิ์ในการแก้ไขข้อมูลนี้'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'height' => 'nullable|numeric|between:0,300',
                'weight' => 'nullable|numeric|between:0,300',
                'allergies' => 'nullable|string|max:1000',
                'chronic_diseases' => 'nullable|string|max:1000',
                'medications' => 'nullable|string|max:1000',
                'blood_type' => 'nullable|string|max:10',
                'rh_factor' => 'nullable|string|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $validator->errors()
                ], 422);
            }

            $healthData = [
                'height_cm' => $request->height,
                'weight_kg' => $request->weight,
                'allergies' => $request->allergies,
                'chronic_diseases' => $request->chronic_diseases,
                'medications' => $request->medications,
                'blood_type' => $request->blood_type,
                'rh_factor' => $request->rh_factor
            ];

            $health->update($healthData);

            return response()->json([
                'status' => 'success',
                'message' => 'อัปเดตข้อมูลสุขภาพเรียบร้อยแล้ว',
                'data' => $health->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating student health info: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูลสุขภาพ'
            ], 500);
        }
    }
}
