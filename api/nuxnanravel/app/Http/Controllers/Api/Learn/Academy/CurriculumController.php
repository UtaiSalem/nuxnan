<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\CurriculumStudent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CurriculumController extends Controller
{
    /**
     * แสดงรายการหลักสูตรทั้งหมดของ Academy
     */
    public function index(Request $request, Academy $academy): JsonResponse
    {
        $query = Curriculum::forAcademy($academy->id)
            ->withCount(['curriculumCourses', 'curriculumStudents as active_students_count' => function ($q) {
                $q->where('status', 'active');
            }]);

        // Filter by academic year
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        // Filter by level
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $curriculums = $query->orderBy('academic_year', 'desc')
            ->orderBy('name')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'curriculums' => $curriculums->items(),
            'pagination' => [
                'current_page' => $curriculums->currentPage(),
                'last_page' => $curriculums->lastPage(),
                'per_page' => $curriculums->perPage(),
                'total' => $curriculums->total(),
            ],
        ]);
    }

    /**
     * สร้างหลักสูตรใหม่
     */
    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'level' => 'nullable|string|max:100',
            'academic_year' => 'nullable|string|max:20',
            'duration_years' => 'nullable|integer|min:1|max:10',
            'total_credits' => 'nullable|integer|min:0',
            'required_credits' => 'nullable|integer|min:0',
            'elective_credits' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'settings' => 'nullable|array',
        ]);

        $curriculum = Curriculum::create([
            'academy_id' => $academy->id,
            ...$validated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้างหลักสูตรสำเร็จ',
            'curriculum' => $curriculum,
        ], 201);
    }

    /**
     * แสดงรายละเอียดหลักสูตร
     */
    public function show(Curriculum $curriculum): JsonResponse
    {
        $curriculum->load([
            'curriculumCourses.course:id,name,code,credit_units,description',
            'curriculumStudents' => function ($q) {
                $q->where('status', 'active')->with('user:id,name,email,profile_photo_path');
            },
        ]);

        $curriculum->loadCount([
            'curriculumCourses',
            'curriculumStudents as active_students_count' => function ($q) {
                $q->where('status', 'active');
            },
            'curriculumStudents as graduated_students_count' => function ($q) {
                $q->where('status', 'graduated');
            },
        ]);

        return response()->json([
            'success' => true,
            'curriculum' => $curriculum,
        ]);
    }

    /**
     * อัปเดตหลักสูตร
     */
    public function update(Request $request, Curriculum $curriculum): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'level' => 'nullable|string|max:100',
            'academic_year' => 'nullable|string|max:20',
            'duration_years' => 'nullable|integer|min:1|max:10',
            'total_credits' => 'nullable|integer|min:0',
            'required_credits' => 'nullable|integer|min:0',
            'elective_credits' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'settings' => 'nullable|array',
        ]);

        $curriculum->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตหลักสูตรสำเร็จ',
            'curriculum' => $curriculum->fresh(),
        ]);
    }

    /**
     * ลบหลักสูตร
     */
    public function destroy(Curriculum $curriculum): JsonResponse
    {
        // ตรวจสอบว่ามีนักเรียนที่กำลังเรียนอยู่หรือไม่
        $activeStudents = $curriculum->curriculumStudents()->where('status', 'active')->count();
        if ($activeStudents > 0) {
            return response()->json([
                'success' => false,
                'message' => "ไม่สามารถลบหลักสูตรได้ เนื่องจากมีนักเรียนกำลังเรียนอยู่ {$activeStudents} คน",
            ], 422);
        }

        $curriculum->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบหลักสูตรสำเร็จ',
        ]);
    }

    /**
     * ดึงรายวิชาในหลักสูตร
     */
    public function getCourses(Request $request, Curriculum $curriculum): JsonResponse
    {
        $query = $curriculum->curriculumCourses()
            ->with('course:id,name,code,credit_units,description,cover,instructor_id,status');

        // Filter by course type
        if ($request->filled('course_type')) {
            $query->where('course_type', $request->course_type);
        }

        // Filter by year level
        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        // Filter by semester
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        $courses = $query->orderBy('year_level')
            ->orderBy('semester')
            ->orderBy('sort_order')
            ->get();

        // จัดกลุ่มตามปีและภาคเรียน
        $groupedCourses = $courses->groupBy('year_level')->map(function ($yearCourses) {
            return $yearCourses->groupBy('semester');
        });

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'grouped_courses' => $groupedCourses,
            'summary' => [
                'total_courses' => $courses->count(),
                'total_credits' => $courses->sum('credits'),
                'required_count' => $courses->where('course_type', 'required')->count(),
                'elective_count' => $courses->where('course_type', 'elective')->count(),
            ],
        ]);
    }

    /**
     * เพิ่มรายวิชาเข้าหลักสูตร
     */
    public function addCourse(Request $request, Curriculum $curriculum): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => [
                'required',
                'exists:courses,id',
                Rule::unique('curriculum_courses')->where(function ($query) use ($curriculum) {
                    return $query->where('curriculum_id', $curriculum->id);
                }),
            ],
            'course_type' => 'nullable|in:required,elective,general',
            'semester' => 'nullable|string|max:10',
            'year_level' => 'nullable|integer|min:1|max:10',
            'credits' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'exists:courses,id',
        ]);

        // ถ้าไม่ได้ระบุ credits ให้ดึงจาก course
        if (! isset($validated['credits'])) {
            $course = Course::find($validated['course_id']);
            $validated['credits'] = $course->credit_units ?? 0;
        }

        $curriculumCourse = CurriculumCourse::create([
            'curriculum_id' => $curriculum->id,
            ...$validated,
        ]);

        $curriculumCourse->load('course:id,name,code,credit_units');

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มรายวิชาเข้าหลักสูตรสำเร็จ',
            'curriculum_course' => $curriculumCourse,
        ], 201);
    }

    /**
     * อัปเดตรายวิชาในหลักสูตร
     */
    public function updateCourse(Request $request, Curriculum $curriculum, CurriculumCourse $curriculumCourse): JsonResponse
    {
        // ตรวจสอบว่า curriculum_course เป็นของ curriculum นี้
        if ($curriculumCourse->curriculum_id !== $curriculum->id) {
            return response()->json([
                'success' => false,
                'message' => 'รายวิชานี้ไม่ได้อยู่ในหลักสูตรนี้',
            ], 404);
        }

        $validated = $request->validate([
            'course_type' => 'nullable|in:required,elective,general',
            'semester' => 'nullable|string|max:10',
            'year_level' => 'nullable|integer|min:1|max:10',
            'credits' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'exists:courses,id',
        ]);

        $curriculumCourse->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตรายวิชาสำเร็จ',
            'curriculum_course' => $curriculumCourse->fresh()->load('course:id,name,code,credit_units'),
        ]);
    }

    /**
     * ลบรายวิชาออกจากหลักสูตร
     */
    public function removeCourse(Curriculum $curriculum, CurriculumCourse $curriculumCourse): JsonResponse
    {
        if ($curriculumCourse->curriculum_id !== $curriculum->id) {
            return response()->json([
                'success' => false,
                'message' => 'รายวิชานี้ไม่ได้อยู่ในหลักสูตรนี้',
            ], 404);
        }

        $curriculumCourse->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบรายวิชาออกจากหลักสูตรสำเร็จ',
        ]);
    }

    /**
     * เพิ่มหลายรายวิชาพร้อมกัน
     */
    public function bulkAddCourses(Request $request, Curriculum $curriculum): JsonResponse
    {
        $validated = $request->validate([
            'courses' => 'required|array|min:1',
            'courses.*.course_id' => 'required|exists:courses,id',
            'courses.*.course_type' => 'nullable|in:required,elective,general',
            'courses.*.semester' => 'nullable|string|max:10',
            'courses.*.year_level' => 'nullable|integer|min:1|max:10',
            'courses.*.credits' => 'nullable|integer|min:0',
        ]);

        $addedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($curriculum, $validated, &$addedCount, &$skippedCount) {
            foreach ($validated['courses'] as $courseData) {
                // ตรวจสอบว่ามีอยู่แล้วหรือไม่
                $exists = CurriculumCourse::where('curriculum_id', $curriculum->id)
                    ->where('course_id', $courseData['course_id'])
                    ->exists();

                if ($exists) {
                    $skippedCount++;

                    continue;
                }

                // ดึง credits จาก course ถ้าไม่ได้ระบุ
                if (! isset($courseData['credits'])) {
                    $course = Course::find($courseData['course_id']);
                    $courseData['credits'] = $course->credit_units ?? 0;
                }

                CurriculumCourse::create([
                    'curriculum_id' => $curriculum->id,
                    ...$courseData,
                ]);

                $addedCount++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "เพิ่มรายวิชาสำเร็จ {$addedCount} วิชา".($skippedCount > 0 ? " (ข้าม {$skippedCount} วิชาที่มีอยู่แล้ว)" : ''),
            'added_count' => $addedCount,
            'skipped_count' => $skippedCount,
        ]);
    }

    /**
     * ดึงนักเรียนในหลักสูตร
     */
    public function getStudents(Request $request, Curriculum $curriculum): JsonResponse
    {
        $query = $curriculum->curriculumStudents()
            ->with('user:id,name,email,profile_photo_path');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by year level
        if ($request->filled('year_level')) {
            $query->where('current_year_level', $request->year_level);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('current_year_level')
            ->orderBy('enrolled_at')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'students' => $students->items(),
            'pagination' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
            ],
        ]);
    }

    /**
     * ลงทะเบียนนักเรียนเข้าหลักสูตร
     */
    public function enrollStudent(Request $request, Curriculum $curriculum): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('curriculum_students')->where(function ($query) use ($curriculum) {
                    return $query->where('curriculum_id', $curriculum->id);
                }),
            ],
            'academy_member_id' => 'nullable|exists:academy_members,id',
            'current_year_level' => 'nullable|integer|min:1|max:10',
        ]);

        $student = CurriculumStudent::create([
            'curriculum_id' => $curriculum->id,
            'user_id' => $validated['user_id'],
            'academy_member_id' => $validated['academy_member_id'] ?? null,
            'current_year_level' => $validated['current_year_level'] ?? 1,
            'status' => 'active',
            'enrolled_at' => now(),
            'expected_graduation' => now()->addYears($curriculum->duration_years ?? 1),
        ]);

        $student->load('user:id,name,email,profile_photo_path');

        return response()->json([
            'success' => true,
            'message' => 'ลงทะเบียนนักเรียนเข้าหลักสูตรสำเร็จ',
            'student' => $student,
        ], 201);
    }

    /**
     * ลงทะเบียนหลายนักเรียนพร้อมกัน
     */
    public function bulkEnrollStudents(Request $request, Curriculum $curriculum): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'current_year_level' => 'nullable|integer|min:1|max:10',
        ]);

        $addedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($curriculum, $validated, &$addedCount, &$skippedCount) {
            foreach ($validated['user_ids'] as $userId) {
                $exists = CurriculumStudent::where('curriculum_id', $curriculum->id)
                    ->where('user_id', $userId)
                    ->exists();

                if ($exists) {
                    $skippedCount++;

                    continue;
                }

                CurriculumStudent::create([
                    'curriculum_id' => $curriculum->id,
                    'user_id' => $userId,
                    'current_year_level' => $validated['current_year_level'] ?? 1,
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'expected_graduation' => now()->addYears($curriculum->duration_years ?? 1),
                ]);

                $addedCount++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "ลงทะเบียนนักเรียนสำเร็จ {$addedCount} คน".($skippedCount > 0 ? " (ข้าม {$skippedCount} คนที่ลงทะเบียนแล้ว)" : ''),
            'added_count' => $addedCount,
            'skipped_count' => $skippedCount,
        ]);
    }

    /**
     * อัปเดตข้อมูลนักเรียนในหลักสูตร
     */
    public function updateStudent(Request $request, Curriculum $curriculum, CurriculumStudent $student): JsonResponse
    {
        if ($student->curriculum_id !== $curriculum->id) {
            return response()->json([
                'success' => false,
                'message' => 'นักเรียนคนนี้ไม่ได้อยู่ในหลักสูตรนี้',
            ], 404);
        }

        $validated = $request->validate([
            'current_year_level' => 'nullable|integer|min:1|max:10',
            'status' => 'nullable|in:active,graduated,dropped,suspended',
            'completed_credits' => 'nullable|integer|min:0',
        ]);

        // ถ้าเปลี่ยนสถานะเป็น graduated ให้บันทึกวันที่จบ
        if (isset($validated['status']) && $validated['status'] === 'graduated') {
            $validated['graduated_at'] = now();
        }

        $student->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตข้อมูลนักเรียนสำเร็จ',
            'student' => $student->fresh()->load('user:id,name,email,profile_photo_path'),
        ]);
    }

    /**
     * ลบนักเรียนออกจากหลักสูตร
     */
    public function removeStudent(Curriculum $curriculum, CurriculumStudent $student): JsonResponse
    {
        if ($student->curriculum_id !== $curriculum->id) {
            return response()->json([
                'success' => false,
                'message' => 'นักเรียนคนนี้ไม่ได้อยู่ในหลักสูตรนี้',
            ], 404);
        }

        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบนักเรียนออกจากหลักสูตรสำเร็จ',
        ]);
    }

    /**
     * ดึงสถิติหลักสูตรของ Academy
     */
    public function getStatistics(Academy $academy): JsonResponse
    {
        $stats = [
            'total_curriculums' => Curriculum::forAcademy($academy->id)->count(),
            'active_curriculums' => Curriculum::forAcademy($academy->id)->active()->count(),
            'total_students' => CurriculumStudent::whereIn('curriculum_id',
                Curriculum::forAcademy($academy->id)->pluck('id')
            )->where('status', 'active')->count(),
            'graduated_students' => CurriculumStudent::whereIn('curriculum_id',
                Curriculum::forAcademy($academy->id)->pluck('id')
            )->where('status', 'graduated')->count(),
        ];

        // รายการหลักสูตรที่มีนักเรียนมากที่สุด
        $popularCurriculums = Curriculum::forAcademy($academy->id)
            ->withCount(['curriculumStudents as active_students_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderByDesc('active_students_count')
            ->limit(5)
            ->get(['id', 'name', 'code', 'academic_year']);

        return response()->json([
            'success' => true,
            'statistics' => $stats,
            'popular_curriculums' => $popularCurriculums,
        ]);
    }

    /**
     * ดึงรายวิชาของ Academy ที่สามารถเพิ่มเข้าหลักสูตรได้
     */
    public function getAvailableCourses(Request $request, Academy $academy, Curriculum $curriculum): JsonResponse
    {
        // รายวิชาที่มีอยู่แล้วในหลักสูตร
        $existingCourseIds = $curriculum->curriculumCourses()->pluck('course_id');

        $query = Course::where('academy_id', $academy->id)
            ->whereNotIn('id', $existingCourseIds)
            ->where('status', 1); // Active courses only

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $courses = $query->orderBy('name')
            ->get(['id', 'name', 'code', 'credit_units', 'description', 'cover']);

        return response()->json([
            'success' => true,
            'courses' => $courses,
        ]);
    }
}
