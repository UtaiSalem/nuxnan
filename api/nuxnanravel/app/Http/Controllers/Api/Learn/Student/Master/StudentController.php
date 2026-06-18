<?php

namespace App\Http\Controllers\Api\Learn\Student\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\StudentHomeVisit;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\StudentResource;

class StudentController extends Controller
{
    /**
     * Display a listing of students with unified master data.
     */
    public function index(Request $request)
    {
        // Enforce academy scope at the route layer: require academy_id and authorize.
        $academyId = $request->input('academy_id');
        if (!$academyId) {
            return response()->json([
                'success' => false,
                'message' => 'academy_id is required to list students'
            ], 422);
        }
        $this->authorize('viewAny', [Student::class, $academyId]);

        $query = Student::query()->where('academy_id', $academyId);

        // Filter by Class Level
        if ($request->has('class_level')) {
            $query->where('class_level', $request->class_level);
        }

        // Filter by Class Section
        if ($request->has('class_section')) {
            $query->where('class_section', $request->class_section);
        }

        // Filter by Status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by Name or ID
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name_th', 'like', "%{$search}%")
                  ->orWhere('last_name_th', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('citizen_id', 'like', "%{$search}%");
            });
        }

        // Eager load important relations
        $students = $query->with(['currentAcademicInfo', 'studentCard'])
                         ->paginate($request->get('per_page', 15));

        return StudentResource::collection($students);
    }

    /**
     * Display the specified student with full master data.
     */
    public function show(Student $student)
    {
        $this->authorize('view', $student);

        $student->load([
            'currentAcademicInfo',
            'academicInfos' => fn($q) => $q->orderBy('academic_year', 'desc'),
            'addresses',
            'contacts',
            'guardians.contacts',
            'healthInfo',
            'documents',
            'studentCard'
        ]);

        return new StudentResource($student);
    }

    /**
     * Display the authenticated student's profile.
     */
    public function myProfile(Request $request)
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียนสำหรับผู้ใช้งานนี้'
            ], 404);
        }

        return $this->show($student);
    }

    /**
     * List pending change requests (for staff/admin).
     */
    public function listRequests(Request $request)
    {
        $academyId = $request->input('academy_id');
        if (!$academyId) {
            return response()->json([
                'success' => false,
                'message' => 'academy_id is required'
            ], 422);
        }
        $this->authorize('approveRequests', [Student::class, $academyId]);

        $query = \App\Models\StudentChangeRequest::with(['student', 'requester'])
            ->where('academy_id', $academyId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate($request->get('per_page', 20))
        ]);
    }

    /**
     * Approve a student change request.
     */
    public function approveRequest(Request $request, $id)
    {
        $changeRequest = \App\Models\StudentChangeRequest::findOrFail($id);
        $this->authorize('approveRequests', [Student::class, $changeRequest->academy_id]);

        if ($changeRequest->status !== 'pending') {
            return response()->json(['error' => 'คำขอนี้ถูกดำเนินการไปแล้ว'], 400);
        }

        // Whitelist allowed models to prevent dynamic class instantiation vulnerability
        $allowedModels = [
            'Student' => \App\Models\Student::class,
            'StudentAddress' => \App\Models\StudentAddress::class,
            'StudentContact' => \App\Models\StudentContact::class,
            'StudentGuardian' => \App\Models\StudentGuardian::class,
            'StudentHealthInfo' => \App\Models\StudentHealthInfo::class,
            'StudentAcademicInfo' => \App\Models\StudentAcademicInfo::class,
        ];

        if (!array_key_exists($changeRequest->model_type, $allowedModels)) {
            return response()->json(['error' => 'ประเภทข้อมูลไม่ถูกต้อง'], 400);
        }

        $modelClass = $allowedModels[$changeRequest->model_type];
        $model = $modelClass::find($changeRequest->model_id);

        if ($model) {
            $field = str_replace(['address.', 'contact.', 'guardian.', 'health.', 'academic.'], '', $changeRequest->field);
            $model->update([$field => $changeRequest->new_value]);
        }

        $changeRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อนุมัติคำขอสำเร็จแล้วและอัปเดตข้อมูลแล้ว'
        ]);
    }

    /**
     * Reject a student change request.
     */
    public function rejectRequest(Request $request, $id)
    {
        $changeRequest = \App\Models\StudentChangeRequest::findOrFail($id);
        $this->authorize('approveRequests', [Student::class, $changeRequest->academy_id]);

        if ($changeRequest->status !== 'pending') {
            return response()->json(['error' => 'คำขอนี้ถูกดำเนินการไปแล้ว'], 400);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $changeRequest->update([
            'status' => 'rejected',
            'reason' => $request->reason,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ปฏิเสธคำขอเรียบร้อยแล้ว'
        ]);
    }

    /**
     * Update student information
     */
    public function updateInfo(Request $request)
    {
        // Resolve student (supports both legacy session and real auth)
        $student = null;
        if ($request->user()) {
            $student = $request->user()->student;
        } elseif (session('homevisit_authenticated') && session('homevisit_user_type') === 'student') {
            $studentCardId = session('homevisit_student_id');
            $studentCard = StudentCard::find($studentCardId);
            if ($studentCard) {
                $student = Student::where('student_id', $studentCard->student_number)
                    ->orWhere('citizen_id', $studentCard->national_id)
                    ->first();
            }
        }
            
        if (!$student) {
            return response()->json(['error' => 'ไม่พบข้อมูลนักเรียนในระบบ'], 404);
        }

        $validatedData = $request->validate([
            'title_prefix_th' => 'nullable|string|max:10',
            'first_name_th' => 'nullable|string|max:50',
            'last_name_th' => 'nullable|string|max:50',
            'title_prefix_en' => 'nullable|string|max:10',
            'first_name_en' => 'nullable|string|max:50',
            'last_name_en' => 'nullable|string|max:50',
            'nickname' => 'nullable|string|max:30',
            'gender' => 'nullable|integer|in:0,1',
            'date_of_birth' => 'nullable|date',
            'nationality' => 'nullable|string|max:50',
            'religion' => 'nullable|string|max:50',
            'profile_image' => 'nullable|string|max:255',
        ]);

        $needsApproval = false;
        $directUpdates = [];

        foreach ($validatedData as $field => $value) {
            if ($value === $student->$field) continue;
            
            $changeRequest = $this->applyUpdate($student, 'Student', $student->id, $field, $value, $student->$field);
            if ($changeRequest) {
                $needsApproval = true;
            } else {
                $directUpdates[$field] = $value;
            }
        }

        if (!empty($directUpdates)) {
            $student->update($directUpdates);
        }

        if ($needsApproval) {
            return response()->json([
                'success' => true,
                'message' => 'ส่งคำขอแก้ไขบางข้อมูลแล้ว รอการอนุมัติ ส่วนข้อมูลที่ไม่ต้องอนุมัติถูกอัปเดตแล้ว',
                'needs_approval' => true,
                'student' => new StudentResource($student->fresh()->load(['addresses', 'contacts', 'guardians', 'healthInfo']))
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตข้อมูลสำเร็จแล้ว',
            'student' => new StudentResource($student->fresh()->load(['addresses', 'contacts', 'guardians', 'healthInfo']))
        ]);
    }

    /**
     * Store a new student home visit record (Integrated system)
     */
    public function storeHomeVisit(Request $request)
    {
        $validatedData = $request->validate([
            // Student Information
            'student_name' => 'required|string|max:255',
            'class' => 'required|string|max:10',
            'birth_date' => 'required|date',
            'gender' => 'required|string|in:ชาย,หญิง',
            'student_id' => 'required|integer|exists:students,id',
            'phone' => 'required|string|max:15',
            
            // Address Information
            'house_number' => 'required|string|max:20',
            'village_number' => 'nullable|string|max:10',
            'village_name' => 'nullable|string|max:100',
            'province' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'subdistrict' => 'required|string|max:100',
            'postal_code' => 'required|string|max:5',
            
            // Family Information
            'father_name' => 'nullable|string|max:255',
            'father_age' => 'nullable|integer|min:0|max:150',
            'father_occupation' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'mother_age' => 'nullable|integer|min:0|max:150',
            'mother_occupation' => 'nullable|string|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_relationship' => 'nullable|string|max:100',
            'siblings_count' => 'nullable|integer|min:0',
            'sibling_position' => 'nullable|integer|min:0',
            
            // Home Environment
            'house_type' => 'nullable|string|max:100',
            'ownership_status' => 'nullable|string|max:100',
            'utilities' => 'nullable|array',
            'study_space' => 'nullable|string|max:100',
            'internet_access' => 'nullable|boolean',
            
            // Student Behavior and Learning
            'learning_behavior' => 'nullable|string',
            'home_responsibilities' => 'nullable|string',
            'extracurricular' => 'nullable|string',
            'academic_support' => 'nullable|string',
            'challenges' => 'nullable|string',
            'family_expectations' => 'nullable|string',
            
            // Visit Information
            'visit_date' => 'required|date',
            'visit_time' => 'required|string',
            'visitor_name' => 'required|string|max:255',
            'visitor_position' => 'required|string|max:255',
            
            // Recommendations
            'recommendations' => 'nullable|string',
            'follow_up' => 'nullable|string',
            'next_visit' => 'nullable|date',
            
            // Photos and signatures
            'photos' => 'nullable|array',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'parent_signature' => 'nullable|string',
            'visitor_signature' => 'nullable|string',
        ]);

        try {
            // Resolve the student instance (Phase 1.5 fix)
            $student = $request->user()?->student ?? \App\Models\Student::find($validatedData['student_id']);
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลนักเรียน'
                ], 404);
            }

            // Handle photo uploads if present
            if ($request->hasFile('photos')) {
                $photosPaths = [];
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('student-home-visits/photos', 'public');
                    $photosPaths[] = $path;
                }
                $validatedData['photos'] = json_encode($photosPaths);
            }

            // Use HYBRID approach to create visit with both relational and standalone data
            $homeVisit = StudentHomeVisit::createFromStudent($student, $validatedData);

            return response()->json([
                'success' => true,
                'message' => 'บันทึกข้อมูลการเยี่ยมบ้านเรียบร้อยแล้ว',
                'data' => $homeVisit
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload student photos
     */
    public function uploadPhotos(Request $request)
    {
        // Resolve student (supports both legacy session and real auth)
        $student = null;
        if ($request->user()) {
            $student = $request->user()->student;
        } elseif (session('homevisit_authenticated') && session('homevisit_user_type') === 'student') {
            $studentCardId = session('homevisit_student_id');
            $studentCard = StudentCard::find($studentCardId);
            if ($studentCard) {
                $student = Student::where('student_id', $studentCard->student_number)
                    ->orWhere('citizen_id', $studentCard->national_id)
                    ->first();
            }
        }

        if (!$student) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg|max:5120',
            'description' => 'nullable|string|max:500'
        ]);

        try {
            $photoPaths = [];
            
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('homevisit/student-photos/' . $student->id, 'public');
                $photoPaths[] = $path;
            }

            // Create or update home visit record for student self-documentation
            $homeVisit = StudentHomeVisit::create([
                'academy_id' => $student->academy_id,
                'student_id' => $student->id,
                'visit_date' => now()->toDateString(),
                'visit_time' => now()->toTimeString(),
                'visitor_name' => 'นักเรียน (อัปโหลดเอง)',
                'visitor_position' => 'นักเรียน',
                'visit_status' => 'completed',
                'observations' => $request->description,
                'notes' => json_encode(['photos' => $photoPaths]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'อัปโหลดรูปภาพสำเร็จแล้ว',
                'photos' => $photoPaths
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ: ' . $e->getMessage()
            ], 500);
        }
    }
}
