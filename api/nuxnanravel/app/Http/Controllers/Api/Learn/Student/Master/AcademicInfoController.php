<?php

namespace App\Http\Controllers\Api\Learn\Student\Master;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Student;
use App\Models\StudentAcademicInfo;
use App\Traits\HandlesStudentUpdates;
use App\Http\Requests\Student\UpdateAcademicInfoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicInfoController extends Controller
{
    use HandlesStudentUpdates;

    /**
     * แสดงข้อมูลประวัติการศึกษาทั้งหมดของนักเรียน
     */
    public function index(Academy $academy, Student $student)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้'
            ], 403);
        }

        $this->authorize('update', $student);

        $academicInfos = $student->academicInfos()
                                ->orderBy('academic_year', 'desc')
                                ->orderBy('is_current', 'desc')
                                ->orderBy('created_at', 'desc')
                                ->get();
        
        return response()->json([
            'success' => true,
            'data' => $academicInfos,
            'current' => $student->currentAcademicInfo,
        ]);
    }

    /**
     * แสดงข้อมูลประวัติการศึกษาปัจจุบันของนักเรียน
     */
    public function show(Academy $academy, Student $student)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้'
            ], 403);
        }

        $this->authorize('update', $student);

        $academicInfo = $student->currentAcademicInfo;
        
        return response()->json([
            'success' => true,
            'data' => $academicInfo,
        ]);
    }

    /**
     * แสดงข้อมูลประวัติการศึกษารายการเดียว
     */
    public function showRecord(Academy $academy, Student $student, StudentAcademicInfo $academicInfo)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้'
            ], 403);
        }

        if ($academicInfo->student_id !== $student->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลประวัติการศึกษา',
            ], 404);
        }

        $this->authorize('update', $student);

        return response()->json([
            'success' => true,
            'data' => $academicInfo,
        ]);
    }

    /**
     * อัพเดทข้อมูลประวัติการศึกษารายการเดียว
     */
    public function update(UpdateAcademicInfoRequest $request, Academy $academy, Student $student, ?StudentAcademicInfo $academicInfo = null)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้'
            ], 403);
        }

        $this->authorize('update', $student);

        $validated = $request->validated();

        try {
            DB::beginTransaction();

            // ถ้าไม่ได้ระบุ academicInfo แสดงว่าเป็นการอัปเดตข้อมูลปัจจุบัน
            if (!$academicInfo) {
                $academicInfo = $student->currentAcademicInfo;
            }

            // ถ้ายังไม่มีข้อมูลเลย ให้สร้างใหม่
            if (!$academicInfo) {
                $academicInfo = new StudentAcademicInfo(['student_id' => $student->id]);
            }

            // Verify ownership if updating existing record
            if ($academicInfo->exists && $academicInfo->student_id !== $student->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถแก้ไขข้อมูลนี้ได้',
                ], 403);
            }

            // ดึงข้อมูล enrollment_date จากตาราง students ถ้าไม่ได้ระบุมา
            if (empty($validated['enrollment_date']) && $student->enrollment_date) {
                $validated['enrollment_date'] = $student->enrollment_date->format('Y-m-d');
            }

            // สร้าง classroom_full อัตโนมัติถ้าไม่ได้กรอกมา
            if (empty($validated['classroom_full']) && !empty($validated['current_grade']) && !empty($validated['current_class'])) {
                $validated['classroom_full'] = $validated['current_grade'] . '/' . $validated['current_class'];
            }

            // For new records (no id yet), staff bypass approval and persist; owner queues approval.
            if (!$academicInfo->exists) {
                // Check if creation needs approval
                $changeRequest = $this->applyUpdate($student, 'StudentAcademicInfo', null, 'academic.create', $validated);
                if ($changeRequest) {
                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'ส่งคำขอเพิ่มประวัติการศึกษาแล้ว รอการอนุมัติ',
                        'needs_approval' => true
                    ]);
                }

                $academicInfo->fill($validated)->save();
                $result = ['updated' => $validated, 'pending' => []];
            } else {
                // Route through approval flow: academic is in default blacklist → owner edits queued.
                $result = $this->processFieldUpdates($student, $academicInfo, 'StudentAcademicInfo', 'academic', $validated);
            }

            // หากตั้งเป็น current ให้ปรับปรุงข้อมูลอื่นๆ (apply only if not pending)
            if (array_key_exists('is_current', $result['updated'] ?? []) && !empty($result['updated']['is_current'])) {
                $student->academicInfos()
                       ->where('id', '!=', $academicInfo->id ?? 0)
                       ->update(['is_current' => false]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => empty($result['pending'])
                    ? 'บันทึกข้อมูลประวัติการศึกษาเรียบร้อยแล้ว'
                    : 'ส่งคำขอแก้ไขประวัติการศึกษารอการอนุมัติแล้ว',
                'data' => $academicInfo->fresh(),
                'pending_fields' => $result['pending'] ?? [],
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * สร้างข้อมูลประวัติการศึกษาใหม่
     */
    public function store(UpdateAcademicInfoRequest $request, Academy $academy, Student $student)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้'
            ], 403);
        }

        $this->authorize('update', $student);

        $validated = $request->validated();

        try {
            DB::beginTransaction();

            // ดึงข้อมูล enrollment_date จากตาราง students ถ้าไม่ได้ระบุมา
            if (empty($validated['enrollment_date']) && $student->enrollment_date) {
                $validated['enrollment_date'] = $student->enrollment_date->format('Y-m-d');
            }

            // Check if creation needs approval
            $changeRequest = $this->applyUpdate($student, 'StudentAcademicInfo', null, 'academic.create', $validated);
            if ($changeRequest) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'ส่งคำขอเพิ่มประวัติการศึกษาแล้ว รอการอนุมัติ',
                    'needs_approval' => true
                ]);
            }

            // สร้างข้อมูลใหม่
            $academicInfo = new StudentAcademicInfo(array_merge($validated, [
                'student_id' => $student->id
            ]));
            
            // สร้าง classroom_full อัตโนมัติถ้าไม่ได้กรอกมา
            if (empty($validated['classroom_full']) && !empty($validated['current_grade']) && !empty($validated['current_class'])) {
                $academicInfo->classroom_full = $validated['current_grade'] . '/' . $validated['current_class'];
            }

            // หากตั้งเป็น current ให้ปรับปรุงข้อมูลอื่นๆ
            if (!empty($validated['is_current'])) {
                // Unset current flag from other records
                $student->academicInfos()->update(['is_current' => false]);
            }

            $academicInfo->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'สร้างข้อมูลประวัติการศึกษาเรียบร้อยแล้ว',
                'data' => $academicInfo,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างข้อมูล: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบข้อมูลประวัติการศึกษา
     */
    public function destroy(Academy $academy, Student $student, StudentAcademicInfo $academicInfo)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้'
            ], 403);
        }

        if ($academicInfo->student_id !== $student->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลประวัติการศึกษา',
            ], 404);
        }

        $this->authorize('update', $student);

        try {
            $academicInfo->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบข้อมูลประวัติการศึกษาเรียบร้อยแล้ว',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ตั้งค่า academic record เป็น current
     */
    public function setCurrent(Academy $academy, Student $student, StudentAcademicInfo $academicInfo)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้'
            ], 403);
        }

        if ($academicInfo->student_id !== $student->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลประวัติการศึกษา',
            ], 404);
        }

        $this->authorize('update', $student);

        try {
            DB::beginTransaction();

            // Unset current flag from other records
            $student->academicInfos()->update(['is_current' => false]);

            // Set this record as current
            $academicInfo->update(['is_current' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ตั้งค่าเป็นข้อมูลปัจจุบันเรียบร้อยแล้ว',
                'data' => $academicInfo,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการตั้งค่า: ' . $e->getMessage(),
            ], 500);
        }
    }
}
