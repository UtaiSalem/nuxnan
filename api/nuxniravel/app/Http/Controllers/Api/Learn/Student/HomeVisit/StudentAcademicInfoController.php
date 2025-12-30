<?php

namespace App\Http\Controllers\Api\Learn\Student\HomeVisit;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAcademicInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentAcademicInfoController extends Controller
{
    /**
     * แสดงข้อมูลประวัติการศึกษาทั้งหมดของนักเรียน
     */
    public function index(Student $student)
    {
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
    public function show(Student $student)
    {
        $academicInfo = $student->currentAcademicInfo;
        
        return response()->json([
            'success' => true,
            'data' => $academicInfo,
        ]);
    }

    /**
     * แสดงข้อมูลประวัติการศึกษารายการเดียว
     */
    public function showRecord(Student $student, StudentAcademicInfo $academicInfo)
    {
        // Verify that this academic info belongs to the student
        if ($academicInfo->student_id !== $student->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลประวัติการศึกษา',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $academicInfo,
        ]);
    }

    /**
     * อัพเดทข้อมูลประวัติการศึกษารายการเดียว
     */
    public function update(Request $request, Student $student, StudentAcademicInfo $academicInfo = null)
    {
        $validated = $request->validate([
            'current_grade' => 'nullable|string|max:10',
            'education_level' => 'nullable|integer|in:0,1,2',
            'current_class' => 'nullable|string|max:10',
            'classroom_full' => 'nullable|string|max:20',
            'school_name' => 'nullable|string|max:200',
            'school_address' => 'nullable|string',
            'school_province' => 'nullable|string|max:100',
            'previous_school_name' => 'nullable|string|max:200',
            'previous_school_province' => 'nullable|string|max:100',
            'previous_grade_level' => 'nullable|string|max:20',
            'disability_type' => 'nullable|string|max:100',
            'special_needs' => 'nullable|string',
            'academic_year' => 'nullable|string|max:10',
            'semester' => 'nullable|integer|in:1,2',
            'enrollment_date' => 'nullable|date',
            'graduation_date' => 'nullable|date|after_or_equal:enrollment_date',
            'study_status' => 'nullable|in:studying,graduated,transferred,dropped,suspended',
            'is_current' => 'nullable|boolean',
            'transfer_reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);

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

            // อัพเดทข้อมูล
            $academicInfo->fill($validated);
            
            // สร้าง classroom_full อัตโนมัติถ้าไม่ได้กรอกมา
            if (empty($validated['classroom_full']) && !empty($validated['current_grade']) && !empty($validated['current_class'])) {
                $academicInfo->classroom_full = $validated['current_grade'] . '/' . $validated['current_class'];
            }

            // หากตั้งเป็น current ให้ปรับปรุงข้อมูลอื่นๆ
            if (!empty($validated['is_current'])) {
                // Unset current flag from other records
                $student->academicInfos()
                       ->where('id', '!=', $academicInfo->id ?? 0)
                       ->update(['is_current' => false]);
            }

            $academicInfo->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'บันทึกข้อมูลประวัติการศึกษาเรียบร้อยแล้ว',
                'data' => $academicInfo->fresh(),
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
    public function store(Request $request, Student $student)
    {
        $validated = $request->validate([
            'current_grade' => 'nullable|string|max:10',
            'education_level' => 'nullable|integer|in:0,1,2',
            'current_class' => 'nullable|string|max:10',
            'classroom_full' => 'nullable|string|max:20',
            'school_name' => 'nullable|string|max:200',
            'school_address' => 'nullable|string',
            'school_province' => 'nullable|string|max:100',
            'previous_school_name' => 'nullable|string|max:200',
            'previous_school_province' => 'nullable|string|max:100',
            'previous_grade_level' => 'nullable|string|max:20',
            'disability_type' => 'nullable|string|max:100',
            'special_needs' => 'nullable|string',
            'academic_year' => 'nullable|string|max:10',
            'semester' => 'nullable|integer|in:1,2',
            'enrollment_date' => 'nullable|date',
            'graduation_date' => 'nullable|date|after_or_equal:enrollment_date',
            'study_status' => 'nullable|in:studying,graduated,transferred,dropped,suspended',
            'is_current' => 'nullable|boolean',
            'transfer_reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // ดึงข้อมูล enrollment_date จากตาราง students ถ้าไม่ได้ระบุมา
            if (empty($validated['enrollment_date']) && $student->enrollment_date) {
                $validated['enrollment_date'] = $student->enrollment_date->format('Y-m-d');
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
    public function destroy(Student $student, StudentAcademicInfo $academicInfo)
    {
        try {
            // ตรวจสอบว่า academic record นี้เป็นของ student คนนี้
            if ($academicInfo->student_id !== $student->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลประวัติการศึกษา',
                ], 404);
            }

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
     * ค้นหานักเรียนตามข้อมูลการศึกษา
     */
    public function searchByAcademicInfo(Request $request)
    {
        $validated = $request->validate([
            'grade' => 'nullable|string|max:10',
            'class' => 'nullable|string|max:10',
            'academic_year' => 'nullable|string|max:10',
            'previous_school' => 'nullable|string|max:200',
        ]);

        $query = Student::with('academicInfo')
            ->whereHas('academicInfo', function($q) use ($validated) {
                if (!empty($validated['grade'])) {
                    $q->where('current_grade', $validated['grade']);
                }
                if (!empty($validated['class'])) {
                    $q->where('current_class', $validated['class']);
                }
                if (!empty($validated['academic_year'])) {
                    $q->where('academic_year', $validated['academic_year']);
                }
                if (!empty($validated['previous_school'])) {
                    $q->where('previous_school_name', 'LIKE', '%' . $validated['previous_school'] . '%');
                }
            });

        $students = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }

    /**
     * ดึงสถิติข้อมูลการศึกษา
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_students_with_academic_info' => StudentAcademicInfo::count(),
                'students_by_grade' => StudentAcademicInfo::select('current_grade', DB::raw('count(*) as total'))
                    ->whereNotNull('current_grade')
                    ->groupBy('current_grade')
                    ->orderBy('current_grade')
                    ->get(),
                'students_by_academic_year' => StudentAcademicInfo::select('academic_year', DB::raw('count(*) as total'))
                    ->whereNotNull('academic_year')
                    ->groupBy('academic_year')
                    ->orderBy('academic_year', 'desc')
                    ->get(),
                'students_with_disabilities' => StudentAcademicInfo::whereNotNull('disability_type')->count(),
                'students_with_special_needs' => StudentAcademicInfo::whereNotNull('special_needs')->count(),
                'transfer_students' => StudentAcademicInfo::whereNotNull('previous_school_name')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงสถิติ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัพเดทข้อมูลหลายรายการพร้อมกัน
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'updates' => 'required|array',
            'updates.*.student_id' => 'required|exists:students,id',
            'updates.*.academic_year' => 'nullable|string|max:10',
            'updates.*.semester' => 'nullable|integer|in:1,2',
            'updates.*.current_grade' => 'nullable|string|max:10',
            'updates.*.current_class' => 'nullable|string|max:10',
        ]);

        try {
            DB::beginTransaction();

            $updated = 0;
            foreach ($validated['updates'] as $update) {
                $student = Student::find($update['student_id']);
                if ($student && $student->academicInfo) {
                    $student->academicInfo->update(array_filter($update, function($value) {
                        return !is_null($value) && $value !== '';
                    }));
                    $updated++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "อัพเดทข้อมูลเรียบร้อย {$updated} รายการ",
                'updated_count' => $updated,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัพเดทข้อมูล: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ตั้งค่า academic record เป็น current
     */
    public function setCurrent(Student $student, StudentAcademicInfo $academicInfo)
    {
        try {
            DB::beginTransaction();

            // ตรวจสอบว่า academic record นี้เป็นของ student คนนี้
            if ($academicInfo->student_id !== $student->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลประวัติการศึกษา',
                ], 404);
            }

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
