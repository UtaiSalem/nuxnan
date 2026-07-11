<?php

namespace App\Http\Controllers\Api\Learn\Student\HomeVisit;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\HomeVisitZone;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\StudentHomeVisit;
use App\Services\StudentEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class AdminController extends Controller
{
    /**
     * Get statistics for academy admin dashboard
     */
    public function statistics(Academy $academy)
    {
        $totalStudents = Student::where('academy_id', $academy->id)->count();
        $totalVisits = StudentHomeVisit::where('academy_id', $academy->id)->count();
        $completedVisits = StudentHomeVisit::where('academy_id', $academy->id)->where('visit_status', 'completed')->count();
        $pendingVisits = StudentHomeVisit::where('academy_id', $academy->id)->where('visit_status', 'pending')->count();

        // Calculate visited students (distinct)
        $visitedStudents = StudentHomeVisit::where('academy_id', $academy->id)
            ->distinct()
            ->count('student_id');
        $visitRate = $totalStudents > 0 ? round(($visitedStudents / $totalStudents) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'statistics' => [
                'totalVisits' => $totalVisits,
                'completedVisits' => $completedVisits,
                'pendingVisits' => $pendingVisits,
                'totalStudents' => $totalStudents,
                'visitedStudents' => $visitedStudents,
                'visitRate' => $visitRate,
            ],
        ]);
    }

    /**
     * Admin Dashboard
     */
    public function dashboard(Academy $academy)
    {
        $stats = [
            'total_students' => Student::where('academy_id', $academy->id)->count(),
            'total_visits' => StudentHomeVisit::where('academy_id', $academy->id)->count(),
            'visits_this_month' => StudentHomeVisit::where('academy_id', $academy->id)
                ->whereMonth('visit_date', now()->month)
                ->whereYear('visit_date', now()->year)
                ->count(),
            'pending_visits' => StudentHomeVisit::where('academy_id', $academy->id)->where('visit_status', 'pending')->count(),
            'completed_visits' => StudentHomeVisit::where('academy_id', $academy->id)->where('visit_status', 'completed')->count(),
        ];

        // Recent visits
        $recentVisits = StudentHomeVisit::where('academy_id', $academy->id)
            ->with('student')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Monthly visit chart data
        $monthlyVisits = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyVisits[] = [
                'month' => $date->format('M Y'),
                'visits' => StudentHomeVisit::where('academy_id', $academy->id)
                    ->whereMonth('visit_date', $date->month)
                    ->whereYear('visit_date', $date->year)
                    ->count(),
            ];
        }

        return response()->json([
            'stats' => $stats,
            'recentVisits' => $recentVisits,
            'monthlyVisits' => $monthlyVisits,
            'allVisits' => $this->getAllVisitsForReports($academy),
            'zones' => HomeVisitZone::where('academy_id', $academy->id)->get(),
        ]);
    }

    /**
     * Admin Dashboard with Mock Data for Testing
     * เฉพาะสำหรับการทดสอบ VisitFeed
     */
    public function dashboardMock(Academy $academy)
    {
        if (! app()->environment('local', 'testing')) {
            abort(404);
        }

        // Generate mock data
        $mockData = $this->generateMockData();

        return response()->json([
            'stats' => $mockData['stats'],
            'recentVisits' => $mockData['recentVisits'],
            'monthlyVisits' => $mockData['monthlyVisits'],
            'allVisits' => $mockData['allVisits'],
            'zones' => $mockData['zones'],
        ]);
    }

    /**
     * Generate mock data for testing
     */
    private function generateMockData()
    {
        $students = [
            ['id' => 1, 'first_name' => 'สมชาย', 'last_name' => 'ใจดี', 'classroom' => 'ม.1/1'],
            ['id' => 2, 'first_name' => 'สมหญิง', 'last_name' => 'มานะ', 'classroom' => 'ม.1/2'],
            ['id' => 3, 'first_name' => 'วิชัย', 'last_name' => 'เรืองแสง', 'classroom' => 'ม.2/1'],
            ['id' => 4, 'first_name' => 'ปราณี', 'last_name' => 'สุขสันต์', 'classroom' => 'ม.2/2'],
            ['id' => 5, 'first_name' => 'อนุชา', 'last_name' => 'ศรีสุข', 'classroom' => 'ม.3/1'],
            ['id' => 6, 'first_name' => 'วิภา', 'last_name' => 'แก้วใส', 'classroom' => 'ม.3/2'],
        ];

        $zones = [
            ['id' => 1, 'name' => 'โซนกลาง', 'zone_name' => 'โซนกลาง'],
            ['id' => 2, 'name' => 'โซนเหนือ', 'zone_name' => 'โซนเหนือ'],
            ['id' => 3, 'name' => 'โซนใต้', 'zone_name' => 'โซนใต้'],
            ['id' => 4, 'name' => 'โซนตะวันออก', 'zone_name' => 'โซนตะวันออก'],
        ];

        $teachers = ['ครูสมศักดิ์ แสงจันทร์', 'ครูวิมล สุขใจ', 'ครูประพันธ์ ปัญญา'];

        $summaries = [
            'เยี่ยมบ้านนักเรียนและพูดคุยกับผู้ปกครองเกี่ยวกับพัฒนาการทางการเรียน นักเรียนมีความตั้งใจเรียนดี มีการทำการบ้านสม่ำเสมอ',
            'พบนักเรียนที่บ้าน สังเกตว่ามีสภาพแวดล้อมที่เอื้อต่อการเรียนรู้ พูดคุยกับผู้ปกครองเรื่องการดูแลสุขภาพ',
            'ติดตามผลการเรียนของนักเรียนที่บ้าน พบว่ามีปัญหาในการเข้าใจเนื้อหาบางวิชา แนะนำให้ผู้ปกครองช่วยติดตาม',
            'เยี่ยมบ้านเพื่อส่งเสริมความสัมพันธ์ระหว่างบ้านและโรงเรียน นักเรียนมีพัฒนาการที่ดีขึ้น',
        ];

        $imageUrls = [
            'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400',
            'https://images.unsplash.com/photo-1588072432836-e10032774350?w=400',
            'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=400',
        ];

        $allVisits = [];
        for ($i = 1; $i <= 30; $i++) {
            $student = $students[array_rand($students)];
            $zone = $zones[array_rand($zones)];
            $status = ['completed', 'in-progress', 'pending', 'cancelled'][rand(0, 3)];
            $daysAgo = rand(0, 60);

            $visit = (object) [
                'id' => $i,
                'student_id' => $student['id'],
                'student' => (object) $student,
                'zone_id' => $zone['id'],
                'zone' => (object) $zone,
                'teacher_name' => $teachers[array_rand($teachers)],
                'visitor_name' => $teachers[array_rand($teachers)],
                'visit_date' => now()->subDays($daysAgo)->toISOString(),
                'visit_status' => $status,
                'summary' => $summaries[array_rand($summaries)],
                'notes' => $summaries[array_rand($summaries)],
                'duration' => $status === 'completed' ? rand(30, 120) : null,
                'risks' => rand(0, 1) ? ['นักเรียนขาดแรงจูงใจในการเรียน', 'สิ่งแวดล้อมมีเสียงรบกวน'] : null,
                'recommendations' => ['กำหนดเวลาเรียนที่บ้านให้ชัดเจน', 'ให้กำลังใจและรางวัล'],
                'follow_up_actions' => ['ติดตามผลการเรียนในเดือนหน้า', 'ประสานงานกับครูประจำชั้น'],
                'next_schedule' => $status === 'completed' && rand(0, 1) ? now()->addDays(30)->toISOString() : null,
                'images' => rand(0, 1) ? array_map(function ($url, $idx) use ($i) {
                    return ['id' => "$i-$idx", 'url' => $url, 'caption' => "ภาพกิจกรรม $idx"];
                }, array_slice($imageUrls, 0, rand(1, 3)), array_keys(array_slice($imageUrls, 0, rand(1, 3)))) : [],
                'created_at' => now()->subDays($daysAgo)->toISOString(),
                'updated_at' => now()->subDays(max(0, $daysAgo - 5))->toISOString(),
            ];

            $allVisits[] = $visit;
        }

        usort($allVisits, function ($a, $b) {
            return strtotime($b->visit_date) - strtotime($a->visit_date);
        });

        return [
            'stats' => [
                'total_students' => count($students),
                'total_visits' => count($allVisits),
                'visits_this_month' => count(array_filter($allVisits, function ($v) {
                    return date('Y-m', strtotime($v->visit_date)) === date('Y-m');
                })),
                'pending_visits' => count(array_filter($allVisits, fn ($v) => $v->visit_status === 'pending')),
                'completed_visits' => count(array_filter($allVisits, fn ($v) => $v->visit_status === 'completed')),
            ],
            'recentVisits' => array_slice($allVisits, 0, 10),
            'monthlyVisits' => [],
            'allVisits' => $allVisits,
            'zones' => $zones,
        ];
    }

    /**
     * Get all visits for reports with full relationships
     */
    private function getAllVisitsForReports(Academy $academy)
    {
        return StudentHomeVisit::where('academy_id', $academy->id)
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'first_name_th', 'last_name_th', 'nickname', 'student_id', 'citizen_id', 'email', 'phone');
                },
                'zone:id,zone_name,zone_code',
                'participants:id,home_visit_id,participant_name,participant_position,participant_role',
                'images:id,home_visit_id,image_path,image_type,image_description',
                'creator:id,name,email',
            ])
            ->withCount('images')
            ->orderBy('visit_date', 'desc')
            ->get();
    }

    /**
     * Student Management
     */
    public function students(Request $request, Academy $academy)
    {
        $query = Student::where('academy_id', $academy->id)->with(['academicInfo.classroom', 'contacts']);

        // Search functionality - include StudentCard search
        if ($request->search) {
            $query->where(function ($q) use ($request, $academy) {
                $q->where('first_name_th', 'like', "%{$request->search}%")
                    ->orWhere('last_name_th', 'like', "%{$request->search}%")
                    ->orWhere('student_id', 'like', "%{$request->search}%")
                    ->orWhere('citizen_id', 'like', "%{$request->search}%")
                  // Also search by matching StudentCard data
                    ->orWhereIn('student_id', function ($subquery) use ($request, $academy) {
                        $subquery->select('student_number')
                            ->from('student_cards')
                            ->where('academy_id', $academy->id)
                            ->where(function ($subq) use ($request) {
                                $subq->where('first_name_thai', 'like', "%{$request->search}%")
                                    ->orWhere('last_name_thai', 'like', "%{$request->search}%")
                                    ->orWhere('student_number', 'like', "%{$request->search}%");
                            });
                    })
                    ->orWhereIn('citizen_id', function ($subquery) use ($request, $academy) {
                        $subquery->select('national_id')
                            ->from('student_cards')
                            ->where('academy_id', $academy->id)
                            ->where(function ($subq) use ($request) {
                                $subq->where('first_name_thai', 'like', "%{$request->search}%")
                                    ->orWhere('last_name_thai', 'like', "%{$request->search}%")
                                    ->orWhere('national_id', 'like', "%{$request->search}%");
                            });
                    });
            });
        }

        // Filter by classroom through academic info
        if ($request->filled('classroom_id')) {
            $query->whereHas('academicInfo', function ($q) use ($request) {
                $q->where('classroom_id', $request->classroom_id);
            });
        } elseif ($request->filled('classroom')) {
            Log::warning('Legacy classroom string filter used in students()');
            $query->whereHas('academicInfo', function ($q) use ($request) {
                $q->where('classroom_full', $request->classroom)
                    ->orWhere('current_class', $request->classroom);
            });
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $students = $query->orderBy('first_name_th')
            ->orderBy('last_name_th')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100))
            ->withQueryString();

        // Get unique classrooms for filter
        $classrooms = Classroom::where('academy_id', $academy->id)
            ->select('id', 'name', 'grade_level', 'section')
            ->orderBy('grade_level')
            ->orderByRaw('CAST(section AS SIGNED)')
            ->get();

        return response()->json([
            'students' => $students,
            'classrooms' => $classrooms,
            'filters' => $request->only(['search', 'classroom_id', 'classroom', 'status']),
        ]);
    }

    /**
     * View/Edit Student Details
     */
    public function showStudent(Academy $academy, $id)
    {
        $student = Student::where('academy_id', $academy->id)
            ->with(['academicInfo', 'addresses', 'contacts', 'guardians.contacts', 'healthInfo'])
            ->findOrFail($id);

        $visits = StudentHomeVisit::where('academy_id', $academy->id)
            ->where('student_id', $id)
            ->orderBy('visit_date', 'desc')
            ->get();

        return response()->json([
            'student' => $student,
            'visits' => $visits,
        ]);
    }

    /**
     * Update Student Information
     */
    public function updateStudent(Request $request, Academy $academy, $id)
    {
        $student = Student::where('academy_id', $academy->id)->findOrFail($id);

        $validatedData = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')->where('academy_id', $academy->id)],
            'national_id' => 'nullable|string|max:13',
            'student_id' => 'nullable|string|max:20',
            'phone_number' => 'nullable|string|max:20',
            'house_number' => 'nullable|string|max:50',
            'subdistrict' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'guardian_full_name' => 'nullable|string|max:200',
            'guardian_phone_number' => 'nullable|string|max:20',
        ]);

        // Update student basic info
        $studentData = [
            'first_name_th' => $validatedData['first_name'],
            'last_name_th' => $validatedData['last_name'],
        ];
        if (array_key_exists('national_id', $validatedData)) {
            $studentData['citizen_id'] = $validatedData['national_id'];
        }
        if (array_key_exists('student_id', $validatedData)) {
            $studentData['student_id'] = $validatedData['student_id'];
        }
        $student->update($studentData);

        // Update classroom enrollment if changed
        $newClassroomId = $validatedData['classroom_id'] ?? null;
        $currentClassroom = $student->currentEnrollment?->classroom;

        if (array_key_exists('classroom_id', $validatedData) && $newClassroomId) {
            $newClassroom = Classroom::where('academy_id', $academy->id)->findOrFail($newClassroomId);
            if (! $currentClassroom) {
                app(StudentEnrollmentService::class)->enrollStudent($student, $newClassroom);
            } elseif ($currentClassroom->id !== (int) $newClassroomId) {
                if ($currentClassroom->academic_year_id === $newClassroom->academic_year_id) {
                    app(StudentEnrollmentService::class)->transferStudent($student, $currentClassroom, $newClassroom);
                } else {
                    app(StudentEnrollmentService::class)->promoteStudent($student, $newClassroom);
                }
            }
        } elseif (array_key_exists('classroom_id', $validatedData) && ! $newClassroomId) {
            if ($currentClassroom) {
                app(StudentEnrollmentService::class)->removeFromClassroom($student, $currentClassroom);
            }
        }

        // Update contact info
        if ($student->contacts->isNotEmpty() && array_key_exists('phone_number', $validatedData)) {
            $student->contacts->first()->update([
                'contact_value' => $validatedData['phone_number'],
            ]);
        }

        // Update address info
        if ($student->addresses->isNotEmpty()) {
            $addressData = [];
            foreach (['house_number', 'subdistrict', 'district', 'province', 'postal_code'] as $field) {
                if (array_key_exists($field, $validatedData)) {
                    $addressData[$field] = $validatedData[$field];
                }
            }
            if (! empty($addressData)) {
                $student->addresses->first()->update($addressData);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทข้อมูลนักเรียนเรียบร้อยแล้ว',
            'student' => $student->load(['academicInfo.classroom', 'contacts', 'addresses']),
        ]);
    }

    /**
     * Home Visit Reports
     */
    public function visits(Request $request, Academy $academy)
    {
        $query = StudentHomeVisit::where('academy_id', $academy->id)->with('student.academicInfo.classroom');

        // Date range filter
        if ($request->date_from) {
            $query->whereDate('visit_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('visit_date', '<=', $request->date_to);
        }

        // Status filter
        if ($request->status) {
            $query->where('visit_status', $request->status);
        }

        if ($request->zone_id) {
            $query->where('zone_id', $request->zone_id);
        }

        if ($request->search) {
            $query->whereHas('student', function ($studentQuery) use ($request) {
                $studentQuery->where('first_name_th', 'like', "%{$request->search}%")
                    ->orWhere('last_name_th', 'like', "%{$request->search}%")
                    ->orWhere('student_id', 'like', "%{$request->search}%");
            });
        }

        // Teacher filter
        if ($request->teacher) {
            $query->where('visitor_name', 'like', "%{$request->teacher}%");
        }

        // Classroom filter
        if ($request->filled('classroom_id')) {
            $query->whereHas('student.academicInfo', function ($q) use ($request) {
                $q->where('classroom_id', $request->classroom_id);
            });
        } elseif ($request->filled('classroom')) {
            Log::warning('Legacy classroom string filter used in visits()');
            $query->whereHas('student.academicInfo', function ($q) use ($request) {
                $q->where('classroom_full', $request->classroom)
                    ->orWhere('current_class', $request->classroom);
            });
        }

        $visits = $query->orderBy('visit_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Get unique classrooms and teachers for filters
        $classrooms = Classroom::where('academy_id', $academy->id)
            ->select('id', 'name', 'grade_level', 'section')
            ->orderBy('grade_level')
            ->orderByRaw('CAST(section AS SIGNED)')
            ->get();

        $teachers = StudentHomeVisit::where('academy_id', $academy->id)
            ->distinct()
            ->orderBy('visitor_name')
            ->pluck('visitor_name')
            ->filter();

        return response()->json([
            'success' => true,
            'visits' => $visits,
            'classrooms' => $classrooms,
            'teachers' => $teachers,
            'filters' => $request->only(['date_from', 'date_to', 'status', 'zone_id', 'search', 'teacher', 'classroom_id', 'classroom']),
        ]);
    }

    /**
     * View Visit Details
     */
    public function showVisit(Academy $academy, $id)
    {
        $visit = StudentHomeVisit::where('academy_id', $academy->id)->with('student')->findOrFail($id);

        return response()->json([
            'visit' => $visit,
        ]);
    }

    public function storeVisit(Request $request, Academy $academy)
    {
        $validated = $this->validateVisit($request, $academy);
        $student = Student::where('academy_id', $academy->id)->findOrFail($validated['student_id']);

        $visit = StudentHomeVisit::create($this->visitPayload($validated, $academy, $student));

        return response()->json([
            'success' => true,
            'visit' => $visit->load('student'),
        ], 201);
    }

    public function updateVisit(Request $request, Academy $academy, $id)
    {
        $visit = StudentHomeVisit::where('academy_id', $academy->id)->findOrFail($id);
        $validated = $this->validateVisit($request, $academy, false);

        $visit->update($this->visitPayload($validated, $academy));

        return response()->json([
            'success' => true,
            'visit' => $visit->fresh()->load('student'),
        ]);
    }

    public function destroyVisit(Academy $academy, $id)
    {
        $visit = StudentHomeVisit::where('academy_id', $academy->id)->findOrFail($id);
        $visit->delete();

        return response()->json(['success' => true]);
    }

    private function validateVisit(Request $request, Academy $academy, bool $requireStudent = true): array
    {
        return $request->validate([
            'student_id' => [$requireStudent ? 'required' : 'sometimes', 'integer', Rule::exists('students', 'id')->where('academy_id', $academy->id)],
            'zone_id' => ['nullable', 'integer', Rule::exists('home_visit_zones', 'id')->where('academy_id', $academy->id)],
            'visit_date' => ['required', 'date'],
            'visit_time' => ['nullable', 'date_format:H:i'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:2000'],
            'observations' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['pending', 'rescheduled', 'scheduled', 'in-progress', 'completed', 'cancelled'])],
        ]);
    }

    private function visitPayload(array $validated, Academy $academy, ?Student $student = null): array
    {
        $user = auth('api')->user();
        $notes = array_filter([
            $validated['purpose'] ?? null,
            isset($validated['address']) ? 'ที่อยู่: '.$validated['address'] : null,
        ]);

        $payload = [
            'academy_id' => $academy->id,
            'student_id' => $student?->id ?? ($validated['student_id'] ?? null),
            'zone_id' => $validated['zone_id'] ?? null,
            'visit_date' => $validated['visit_date'],
            'visit_time' => $validated['visit_time'] ?? null,
            'visitor_name' => $user?->name ?? 'Academy administrator',
            'visitor_position' => 'Academy staff',
            'visit_status' => $validated['status'],
            'observations' => $validated['observations'] ?? null,
            'notes' => $notes ? implode("\n", $notes) : null,
            'recommendations' => $validated['recommendations'] ?? null,
            'created_by' => $user?->id,
        ];

        if ($payload['student_id'] === null) {
            unset($payload['student_id']);
        }
        if ($payload['created_by'] === null) {
            unset($payload['created_by']);
        }

        return $payload;
    }

    /**
     * Update Visit Status
     */
    public function updateVisitStatus(Request $request, Academy $academy, $id)
    {
        $visit = StudentHomeVisit::where('academy_id', $academy->id)->findOrFail($id);

        $request->validate([
            'visit_status' => 'required|in:pending,in-progress,completed,cancelled',
            'admin_notes' => 'nullable|string',
        ]);

        $visit->update([
            'visit_status' => $request->visit_status,
            'admin_notes' => $request->admin_notes,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทสถานะการเยี่ยมบ้านเรียบร้อยแล้ว',
            'visit' => $visit,
        ]);
    }

    /**
     * Export Reports
     */
    public function exportVisits(Request $request, Academy $academy)
    {
        $query = StudentHomeVisit::where('academy_id', $academy->id)->with('student.academicInfo.classroom');

        // Apply same filters as visits method
        if ($request->date_from) {
            $query->whereDate('visit_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('visit_date', '<=', $request->date_to);
        }
        if ($request->status) {
            $query->where('visit_status', $request->status);
        }
        if ($request->zone_id) {
            $query->where('zone_id', $request->zone_id);
        }

        $visits = $query->orderBy('visit_date', 'desc')->get();

        $statusLabels = [
            'pending' => 'รอดำเนินการ',
            'completed' => 'เยี่ยมแล้ว',
            'cancelled' => 'ยกเลิก',
        ];

        $rows = $visits->map(function ($visit) use ($statusLabels) {
            $studentName = 'N/A';
            if ($visit->student) {
                $studentName = trim($visit->student->first_name_th.' '.$visit->student->last_name_th);
            }

            $classroomName = 'N/A';
            if ($visit->student && $visit->student->currentAcademicInfo) {
                $classroomName = $visit->student->currentAcademicInfo->classroom_full ?? 'N/A';
            }

            $visitDate = 'N/A';
            if ($visit->visit_date) {
                $visitDate = $visit->visit_date instanceof \DateTimeInterface
                    ? $visit->visit_date->format('d/m/Y')
                    : date('d/m/Y', strtotime($visit->visit_date));
            }

            return [
                'visit_date' => $visitDate,
                'student_name' => $studentName,
                'classroom' => $classroomName,
                'visitor_name' => $visit->visitor_name ?? '',
                'status_key' => $visit->visit_status,
                'status_label' => $statusLabels[$visit->visit_status] ?? $visit->visit_status,
                'notes' => $visit->notes ?? '',
                'observations' => $visit->observations ?? '',
            ];
        })->all();

        if (strtolower((string) $request->input('format', 'csv')) === 'pdf') {
            return $this->exportVisitsPdf($academy, $rows, $request);
        }

        return $this->exportVisitsCsv($rows);
    }

    /**
     * Stream the mapped home-visit rows as a UTF-8 CSV download.
     */
    private function exportVisitsCsv(array $rows)
    {
        $filename = 'home-visits-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'วันที่เยี่ยม',
                'ชื่อ-นามสกุลนักเรียน',
                'ชั้นเรียน',
                'ครูผู้เยี่ยม',
                'สถานะ',
                'หัวข้อการเยี่ยม',
                'สรุปผลการเยี่ยม',
            ]);

            foreach ($rows as $row) {
                fputcsv($file, [
                    $row['visit_date'],
                    $row['student_name'],
                    $row['classroom'],
                    $row['visitor_name'],
                    $row['status_key'],
                    $row['notes'],
                    $row['observations'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Render the mapped home-visit rows as a Thai landscape PDF via mPDF.
     */
    private function exportVisitsPdf(Academy $academy, array $rows, Request $request)
    {
        $statusLabels = [
            'pending' => 'รอดำเนินการ',
            'completed' => 'เยี่ยมแล้ว',
            'cancelled' => 'ยกเลิก',
        ];

        $filterParts = [];
        if ($request->date_from) {
            $filterParts[] = 'ตั้งแต่ '.$request->date_from;
        }
        if ($request->date_to) {
            $filterParts[] = 'ถึง '.$request->date_to;
        }
        if ($request->status) {
            $filterParts[] = 'สถานะ '.($statusLabels[$request->status] ?? $request->status);
        }

        $html = view('exports.home-visits-pdf', [
            'academyName' => $academy->name,
            'rows' => $rows,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'filterSummary' => implode(' · ', $filterParts),
        ])->render();

        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'garuda',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'tempDir' => $tempDir,
        ]);
        $mpdf->WriteHTML($html);

        $filename = 'home-visits-'.now()->format('Y-m-d').'.pdf';

        return response(
            $mpdf->Output($filename, Destination::STRING_RETURN),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename={$filename}",
            ]
        );
    }

    /**
     * Admin Settings
     */
    public function settings(Academy $academy)
    {
        return response()->json(['success' => true]);
    }

    /**
     * Reports summary stub
     */
    public function reports(Academy $academy)
    {
        return response()->json(['message' => 'Reports not implemented yet']);
    }

    /**
     * Export reports stub
     */
    public function exportReports(Request $request, Academy $academy)
    {
        return response()->json(['message' => 'Export reports not implemented yet']);
    }

    /**
     * Logout Admin
     */
    public function logout(Academy $academy)
    {
        session()->forget('homevisit_admin_authenticated');

        return redirect()->route('homevisit.login')->with('success', 'ออกจากระบบเรียบร้อยแล้ว');
    }

    /**
     * API: Get all visits with filters for reports
     */
    public function getAllVisits(Request $request, Academy $academy)
    {
        $query = StudentHomeVisit::where('academy_id', $academy->id)->with([
            'student' => function ($q) {
                $q->select('id', 'first_name_th', 'last_name_th', 'nickname', 'student_id', 'citizen_id', 'email', 'phone');
            },
            'zone:id,zone_name,zone_code',
            'participants',
            'images',
            'creator:id,name,email',
        ]);

        // Apply filters
        if ($request->filled('startDate')) {
            $query->where('visit_date', '>=', $request->startDate);
        }

        if ($request->filled('endDate')) {
            $query->where('visit_date', '<=', $request->endDate);
        }

        if ($request->filled('status')) {
            $query->where('visit_status', $request->status);
        }

        if ($request->filled('zoneId')) {
            $query->where('zone_id', $request->zoneId)
                ->whereIn('zone_id', function ($subq) use ($academy) {
                    $subq->select('id')->from('home_visit_zones')->where('academy_id', $academy->id);
                });
        }

        if ($request->filled('teacherName')) {
            $query->where(function ($q) use ($request) {
                $q->where('visitor_name', 'like', "%{$request->teacherName}%")
                    ->orWhereHas('participants', function ($pq) use ($request) {
                        $pq->where('participant_name', 'like', "%{$request->teacherName}%");
                    });
            });
        }

        if ($request->filled('studentName')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('first_name_th', 'like', "%{$request->studentName}%")
                    ->orWhere('last_name_th', 'like', "%{$request->studentName}%");
            });
        }

        // Count images
        $query->withCount('images');

        // Sort
        $sortBy = $request->get('sortBy', 'visit_date_desc');
        switch ($sortBy) {
            case 'visit_date_asc':
                $query->orderBy('visit_date', 'asc');
                break;
            case 'student_name':
                $query->join('students', 'student_home_visits.student_id', '=', 'students.id')
                    ->orderBy('students.first_name_th', 'asc')
                    ->select('student_home_visits.*');
                break;
            case 'status':
                $query->orderBy('visit_status', 'asc');
                break;
            default: // visit_date_desc
                $query->orderBy('visit_date', 'desc');
        }

        return response()->json($query->get());
    }
}
