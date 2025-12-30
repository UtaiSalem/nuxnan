<?php

namespace App\Http\Controllers\Api\Learn\Student\HomeVisit;

use App\Http\Controllers\Controller;
use App\Models\StudentHomeVisit;
use App\Models\Student;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function __construct()
    {
        // Ensure admin is authenticated
        $this->middleware(function ($request, $next) {
            if (!session('homevisit_admin_authenticated')) {
                return redirect()->route('homevisit.login');
            }
            return $next($request);
        });
    }

    /**
     * Admin Dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_students' => Student::count(),
            'total_visits' => StudentHomeVisit::count(),
            'visits_this_month' => StudentHomeVisit::whereMonth('visit_date', now()->month)
                ->whereYear('visit_date', now()->year)
                ->count(),
            'pending_visits' => StudentHomeVisit::where('visit_status', 'pending')->count(),
            'completed_visits' => StudentHomeVisit::where('visit_status', 'completed')->count(),
        ];

        // Recent visits
        $recentVisits = StudentHomeVisit::with('student')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Monthly visit chart data
        $monthlyVisits = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyVisits[] = [
                'month' => $date->format('M Y'),
                'visits' => StudentHomeVisit::whereMonth('visit_date', $date->month)
                    ->whereYear('visit_date', $date->year)
                    ->count()
            ];
        }

        return response()->json([
            'stats' => $stats,
            'recentVisits' => $recentVisits,
            'monthlyVisits' => $monthlyVisits,
            'allVisits' => $this->getAllVisitsForReports(),
            'zones' => \App\Models\HomeVisitZone::all(),
        ]);
    }

    /**
     * Admin Dashboard with Mock Data for Testing
     * เฉพาะสำหรับการทดสอบ VisitFeed
     */
    public function dashboardMock()
    {
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
            
            $visit = (object)[
                'id' => $i,
                'student_id' => $student['id'],
                'student' => (object)$student,
                'zone_id' => $zone['id'],
                'zone' => (object)$zone,
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
                'images' => rand(0, 1) ? array_map(function($url, $idx) use ($i) {
                    return ['id' => "$i-$idx", 'url' => $url, 'caption' => "ภาพกิจกรรม $idx"];
                }, array_slice($imageUrls, 0, rand(1, 3)), array_keys(array_slice($imageUrls, 0, rand(1, 3)))) : [],
                'created_at' => now()->subDays($daysAgo)->toISOString(),
                'updated_at' => now()->subDays(max(0, $daysAgo - 5))->toISOString(),
            ];
            
            $allVisits[] = $visit;
        }

        usort($allVisits, function($a, $b) {
            return strtotime($b->visit_date) - strtotime($a->visit_date);
        });

        return [
            'stats' => [
                'total_students' => count($students),
                'total_visits' => count($allVisits),
                'visits_this_month' => count(array_filter($allVisits, function($v) {
                    return date('Y-m', strtotime($v->visit_date)) === date('Y-m');
                })),
                'pending_visits' => count(array_filter($allVisits, fn($v) => $v->visit_status === 'pending')),
                'completed_visits' => count(array_filter($allVisits, fn($v) => $v->visit_status === 'completed')),
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
    private function getAllVisitsForReports()
    {
        return StudentHomeVisit::with([
            'student' => function($query) {
                $query->select('id', 'first_name_th', 'last_name_th', 'nickname', 'student_id', 'citizen_id', 'email', 'phone');
            },
            'zone:id,zone_name,zone_code',
            'participants:id,home_visit_id,participant_name,participant_position,participant_role',
            'images:id,home_visit_id,image_path,image_type,image_description',
            'creator:id,name,email'
        ])
        ->withCount('images')
        ->orderBy('visit_date', 'desc')
        ->get();
    }

    /**
     * Student Management
     */
    public function students(Request $request)
    {
        $query = Student::with(['academicInfo', 'contacts']);

        // Search functionality - include StudentCard search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('first_name_th', 'like', "%{$request->search}%")
                  ->orWhere('last_name_th', 'like', "%{$request->search}%")
                  ->orWhere('student_id', 'like', "%{$request->search}%")
                  ->orWhere('citizen_id', 'like', "%{$request->search}%")
                  // Also search by matching StudentCard data
                  ->orWhereIn('student_id', function($subquery) use ($request) {
                      $subquery->select('student_number')
                               ->from('student_cards')
                               ->where('first_name_thai', 'like', "%{$request->search}%")
                               ->orWhere('last_name_thai', 'like', "%{$request->search}%")
                               ->orWhere('student_number', 'like', "%{$request->search}%");
                  })
                  ->orWhereIn('citizen_id', function($subquery) use ($request) {
                      $subquery->select('national_id')
                               ->from('student_cards')
                               ->where('first_name_thai', 'like', "%{$request->search}%")
                               ->orWhere('last_name_thai', 'like', "%{$request->search}%")
                               ->orWhere('national_id', 'like', "%{$request->search}%");
                  });
            });
        }

        // Filter by classroom through academic info
        if ($request->classroom) {
            $query->whereHas('academicInfo', function($q) use ($request) {
                $q->where('classroom', $request->classroom);
            });
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $students = $query->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        // Get unique classrooms for filter from academic info
        $classrooms = Student::join('student_academic_info', 'students.id', '=', 'student_academic_info.student_id')
            ->distinct()
            ->orderBy('student_academic_info.classroom')
            ->pluck('student_academic_info.classroom')
            ->filter();

        return response()->json([
            'students' => $students,
            'classrooms' => $classrooms,
            'filters' => $request->only(['search', 'classroom', 'status']),
        ]);
    }

    /**
     * View/Edit Student Details
     */
    public function showStudent($id)
    {
        $student = Student::with(['academicInfo', 'addresses', 'contacts', 'guardians.contacts', 'healthInfo'])
            ->findOrFail($id);
        
        $visits = StudentHomeVisit::where('student_id', $id)
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
    public function updateStudent(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validatedData = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'classroom' => 'required|string|max:20',
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
        $student->update([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'national_id' => $validatedData['national_id'],
            'student_id' => $validatedData['student_id'],
        ]);

        // Update academic info
        if ($student->academicInfo) {
            $student->academicInfo->update([
                'classroom' => $validatedData['classroom'],
            ]);
        }

        // Update contact info
        if ($student->contacts->isNotEmpty()) {
            $student->contacts->first()->update([
                'phone' => $validatedData['phone_number'],
            ]);
        }

        // Update address info
        if ($student->addresses->isNotEmpty()) {
            $student->addresses->first()->update([
                'house_number' => $validatedData['house_number'],
                'subdistrict' => $validatedData['subdistrict'],
                'district' => $validatedData['district'],
                'province' => $validatedData['province'],
                'postal_code' => $validatedData['postal_code'],
            ]);
        }

        return redirect()->back()->with('success', 'อัพเดทข้อมูลนักเรียนเรียบร้อยแล้ว');
    }

    /**
     * Home Visit Reports
     */
    public function visits(Request $request)
    {
        $query = StudentHomeVisit::with('student');

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

        // Teacher filter
        if ($request->teacher) {
            $query->where('teacher_name', 'like', "%{$request->teacher}%");
        }

        // Classroom filter
        if ($request->classroom) {
            $query->whereHas('student.academicInfo', function($q) use ($request) {
                $q->where('classroom', $request->classroom);
            });
        }

        $visits = $query->orderBy('visit_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Get unique classrooms and teachers for filters
        $classrooms = Student::join('student_academic_info', 'students.id', '=', 'student_academic_info.student_id')
            ->distinct()
            ->orderBy('student_academic_info.classroom')
            ->pluck('student_academic_info.classroom')
            ->filter();

        $teachers = StudentHomeVisit::distinct()
            ->orderBy('teacher_name')
            ->pluck('teacher_name')
            ->filter();

        return response()->json([
            'visits' => $visits,
            'classrooms' => $classrooms,
            'teachers' => $teachers,
            'filters' => $request->only(['date_from', 'date_to', 'status', 'teacher', 'classroom']),
        ]);
    }

    /**
     * View Visit Details
     */
    public function showVisit($id)
    {
        $visit = StudentHomeVisit::with('student')->findOrFail($id);

        return response()->json([
            'visit' => $visit,
        ]);
    }

    /**
     * Update Visit Status
     */
    public function updateVisitStatus(Request $request, $id)
    {
        $visit = StudentHomeVisit::findOrFail($id);

        $request->validate([
            'visit_status' => 'required|in:pending,in-progress,completed,cancelled',
            'admin_notes' => 'nullable|string',
        ]);

        $visit->update([
            'visit_status' => $request->visit_status,
            'admin_notes' => $request->admin_notes,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'อัพเดทสถานะการเยี่ยมบ้านเรียบร้อยแล้ว');
    }

    /**
     * Export Reports
     */
    public function exportVisits(Request $request)
    {
        $query = StudentHomeVisit::with('student');

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

        $visits = $query->orderBy('visit_date', 'desc')->get();

        $filename = 'home-visits-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function() use ($visits) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Header
            fputcsv($file, [
                'วันที่เยี่ยม',
                'ชื่อ-นามสกุลนักเรียน',
                'ชั้นเรียน',
                'ครูผู้เยี่ยม',
                'สถานะ',
                'หัวข้อการเยี่ยม',
                'สรุปผลการเยี่ยม'
            ]);

            foreach ($visits as $visit) {
                fputcsv($file, [
                    $visit->visit_date->format('d/m/Y'),
                    $visit->student->full_name_thai ?? 'N/A',
                    $visit->student->classroom ?? 'N/A',
                    $visit->teacher_name,
                    $visit->visit_status,
                    $visit->visit_purpose,
                    $visit->visit_summary,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Admin Settings
     */
    public function settings()
    {
        return response()->json(['success' => true]);
    }

    /**
     * Logout Admin
     */
    public function logout()
    {
        session()->forget('homevisit_admin_authenticated');
        return redirect()->route('homevisit.login')->with('success', 'ออกจากระบบเรียบร้อยแล้ว');
    }

    /**
     * API: Get all visits with filters for reports
     */
    public function getAllVisits(Request $request)
    {
        $query = StudentHomeVisit::with([
            'student' => function($q) {
                $q->select('id', 'first_name_th', 'last_name_th', 'nickname', 'student_id', 'citizen_id', 'email', 'phone');
            },
            'zone:id,zone_name,zone_code',
            'participants',
            'images',
            'creator:id,name,email'
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
            $query->where('zone_id', $request->zoneId);
        }

        if ($request->filled('teacherName')) {
            $query->where(function($q) use ($request) {
                $q->where('visitor_name', 'like', "%{$request->teacherName}%")
                  ->orWhereHas('participants', function($pq) use ($request) {
                      $pq->where('participant_name', 'like', "%{$request->teacherName}%");
                  });
            });
        }

        if ($request->filled('studentName')) {
            $query->whereHas('student', function($q) use ($request) {
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

    /**
     * Download individual visit report as PDF
     */
    public function downloadReport($visitId)
    {
        $visit = StudentHomeVisit::with([
            'student',
            'zone',
            'participants',
            'images',
            'creator'
        ])->findOrFail($visitId);

        // TODO: Implement PDF generation with DomPDF or TCPDF
        // For now, return JSON
        return response()->json([
            'message' => 'PDF generation not yet implemented',
            'visit' => $visit
        ]);

        /* Example implementation with DomPDF:
        $pdf = \PDF::loadView('reports.home-visit-detail', [
            'visit' => $visit
        ]);
        
        return $pdf->download("home-visit-report-{$visitId}.pdf");
        */
    }

    /**
     * Export multiple visits to Excel
     */
    public function exportToExcel(Request $request)
    {
        try {
            $visitIds = $request->get('visits', []);
            
            // Validate we have visits to export
            if (empty($visitIds)) {
                return response()->json([
                    'message' => 'ไม่มีข้อมูลที่จะส่งออก กรุณาเลือกข้อมูลก่อน'
                ], 400);
            }
            
            $visits = StudentHomeVisit::with([
                'student',
                'zone',
                'participants',
                'images'
            ])->whereIn('id', $visitIds)->get();

            // Check if visits found
            if ($visits->isEmpty()) {
                return response()->json([
                    'message' => 'ไม่พบข้อมูลการเยี่ยมบ้านที่เลือก'
                ], 404);
            }

            // Generate filename
            $filename = 'home-visits-' . now()->format('Y-m-d') . '.xlsx';

            // Use Laravel Excel to export
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\HomeVisitsExport($visits),
                $filename,
                \Maatwebsite\Excel\Excel::XLSX,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Excel Export Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'เกิดข้อผิดพลาดในการส่งออกข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export multiple visits to PDF
     */
    public function exportToPDF(Request $request)
    {
        $visitIds = $request->get('visits', []);
        
        $visits = StudentHomeVisit::with([
            'student',
            'zone',
            'participants',
            'images'
        ])->whereIn('id', $visitIds)->get();

        $filters = $request->get('filters');

        // TODO: Implement PDF generation with DomPDF or TCPDF
        // For now, return JSON
        return response()->json([
            'message' => 'PDF export not yet implemented',
            'visits_count' => $visits->count(),
            'filters' => $filters
        ]);

        /* Example implementation with DomPDF:
        $pdf = \PDF::loadView('reports.home-visits-summary', [
            'visits' => $visits,
            'filters' => $filters,
            'generated_at' => now()->format('d/m/Y H:i:s')
        ]);
        
        return $pdf->download('home-visits-summary-' . now()->format('Y-m-d') . '.pdf');
        */
    }
}
