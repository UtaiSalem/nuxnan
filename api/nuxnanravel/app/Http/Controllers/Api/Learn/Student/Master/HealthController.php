<?php

namespace App\Http\Controllers\Api\Learn\Student\Master;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Student;
use App\Models\StudentHealthInfo;
use App\Http\Requests\Student\UpdateHealthRequest;
use App\Traits\HandlesStudentUpdates;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    use HandlesStudentUpdates;

    /**
     * Show student health information
     */
    public function show(Academy $academy, Student $student): JsonResponse
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้'
            ], 403);
        }

        $this->authorize('update', $student);

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
    public function store(UpdateHealthRequest $request, Academy $academy, Student $student): JsonResponse
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้'
            ], 403);
        }

        $this->authorize('update', $student);

        try {
            // Check if health info already exists
            $existingHealth = StudentHealthInfo::where('student_id', $student->id)->first();
            if ($existingHealth) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ข้อมูลสุขภาพมีอยู่แล้ว กรุณาใช้การอัปเดต'
                ], 409);
            }

            $validated = $request->validated();
            $healthData = [
                'student_id' => $student->id,
                'height_cm' => $validated['height'] ?? null,
                'weight_kg' => $validated['weight'] ?? null,
                'allergies' => $validated['allergies'] ?? null,
                'chronic_diseases' => $validated['chronic_diseases'] ?? null,
                'medications' => $validated['medications'] ?? null,
                'blood_type' => $validated['blood_type'] ?? null,
                'rh_factor' => $validated['rh_factor'] ?? null
            ];

            // Check if creation needs approval
            $changeRequest = $this->applyUpdate($student, 'StudentHealthInfo', null, 'health.create', $healthData);
            if ($changeRequest) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'ส่งคำขอเพิ่มข้อมูลสุขภาพแล้ว รอการอนุมัติ',
                    'needs_approval' => true
                ]);
            }

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
    public function update(UpdateHealthRequest $request, Academy $academy, Student $student, StudentHealthInfo $health): JsonResponse
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้'
            ], 403);
        }

        if ($health->student_id !== $student->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่มีสิทธิ์ในการแก้ไขข้อมูลนี้'
            ], 403);
        }

        $this->authorize('update', $student);

        try {
            $validated = $request->validated();
            $healthData = [];
            if (array_key_exists('height', $validated)) $healthData['height_cm'] = $validated['height'];
            if (array_key_exists('weight', $validated)) $healthData['weight_kg'] = $validated['weight'];
            if (array_key_exists('allergies', $validated)) $healthData['allergies'] = $validated['allergies'];
            if (array_key_exists('chronic_diseases', $validated)) $healthData['chronic_diseases'] = $validated['chronic_diseases'];
            if (array_key_exists('medications', $validated)) $healthData['medications'] = $validated['medications'];
            if (array_key_exists('blood_type', $validated)) $healthData['blood_type'] = $validated['blood_type'];
            if (array_key_exists('rh_factor', $validated)) $healthData['rh_factor'] = $validated['rh_factor'];

            // Route through processFieldUpdates
            $result = $this->processFieldUpdates($student, $health, 'StudentHealthInfo', 'health', $healthData);

            return response()->json([
                'status' => 'success',
                'message' => empty($result['pending'])
                    ? 'อัปเดตข้อมูลสุขภาพเรียบร้อยแล้ว'
                    : 'ส่งคำขอแก้ไขข้อมูลสุขภาพรอการอนุมัติแล้ว',
                'data' => $health->fresh(),
                'pending_fields' => $result['pending'],
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
