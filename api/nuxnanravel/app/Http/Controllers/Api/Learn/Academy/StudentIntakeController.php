<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\Enrollment\CheckStudentDuplicateRequest;
use App\Http\Requests\Academy\Enrollment\StoreStudentIntakeRequest;
use App\Http\Resources\Learn\Academy\Enrollment\StudentIntakeResource;
use App\Models\Academy;
use App\Models\Student;
use App\Services\StudentIntakeService;
use Illuminate\Http\JsonResponse;

class StudentIntakeController extends Controller
{
    public function __construct(private readonly StudentIntakeService $studentIntakeService) {}

    public function store(StoreStudentIntakeRequest $request, Academy $academy): JsonResponse
    {
        $result = $this->studentIntakeService->intake($academy, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Student intake completed successfully.',
            'data' => (new StudentIntakeResource($result))->resolve($request),
        ], 201);
    }

    public function duplicateCheck(CheckStudentDuplicateRequest $request, Academy $academy): JsonResponse
    {
        $duplicates = $this->studentIntakeService->findDuplicates(
            $academy,
            $request->validated('student_id'),
            $request->validated('citizen_id'),
        );

        return response()->json(['success' => true, 'has_duplicates' => $duplicates !== [], 'data' => $duplicates]);
    }

    public function stats(Academy $academy): JsonResponse
    {
        return response()->json(['stats' => [
            'totalStudents' => Student::where('academy_id', $academy->id)->where('status', 'active')->count(),
            'newIntakes' => Student::where('academy_id', $academy->id)->where('created_at', '>=', now()->subMonths(6))->count(),
            'unassigned' => Student::where('academy_id', $academy->id)->whereDoesntHave('classroomStudents', fn($q) => $q->where('status', 'active'))->count(),
            'pendingActivation' => Student::where('academy_id', $academy->id)->where('account_status', 'pending_activation')->count(),
        ]]);
    }

    public function export(Academy $academy)
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=students_export.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];
        
        $callback = function() use ($academy) {
            $file = fopen('php://output', 'w');
            // Adding UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['รหัสนักเรียน', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'เลขประชาชน', 'สถานะ']);
            
            $sanitize = function($val) {
                if ($val !== null && preg_match('/^[=\-+@]/', $val)) {
                    return "'" . $val;
                }
                return $val;
            };

            foreach (Student::where('academy_id', $academy->id)->cursor() as $student) {
                fputcsv($file, [
                    $sanitize($student->student_id),
                    $sanitize($student->title_prefix_th),
                    $sanitize($student->first_name_th),
                    $sanitize($student->last_name_th),
                    $sanitize($student->citizen_id),
                    $sanitize($student->status)
                ]);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
