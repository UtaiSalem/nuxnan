<?php

namespace App\Http\Controllers\Api\Learn\Student\HomeVisit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\StudentHomeVisit;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    /**
     * Show student profile page
     */
    public function profile()
    {
        // Check if student is authenticated
        if (!session('homevisit_authenticated') || session('homevisit_user_type') !== 'student') {
            return redirect()->route('homevisit.login');
        }

        $studentCardId = session('homevisit_student_id');
        
        // Find StudentCard first (for authentication)
        $studentCard = StudentCard::find($studentCardId);
        if (!$studentCard) {
            return redirect()->route('homevisit.login')->withErrors(['error' => 'ไม่พบข้อมูลบัตรนักเรียน']);
        }
        
        // Find corresponding student in normalized database
        $student = Student::where('student_id', $studentCard->student_number)
            ->orWhere('citizen_id', $studentCard->national_id)
            ->with([
                'academicInfo', 
                'addresses', 
                'contacts', 
                'guardians.contacts', 
                'healthInfo',
                'documents'
            ])
            ->first();
        
        if (!$student) {
            return redirect()->route('homevisit.login')->withErrors(['error' => 'ไม่พบข้อมูลนักเรียนในระบบ']);
        }
        
        // Get student's home visit records using HYBRID approach
        $homeVisits = StudentHomeVisit::where(function($query) use ($student) {
            $query->where('student_id', $student->id);
        })
        ->orderBy('visit_date', 'desc')
        ->get();

        return response()->json([
            'student' => $student,
            'studentCard' => $studentCard, // Include card info for profile image, etc.
            'homeVisits' => $homeVisits
        ]);
    }

    /**
     * Update student information
     */
    public function updateInfo(Request $request)
    {
        if (!session('homevisit_authenticated') || session('homevisit_user_type') !== 'student') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $studentCardId = session('homevisit_student_id');
        $studentCard = StudentCard::findOrFail($studentCardId);
        
        // Find corresponding student in normalized database
        $student = Student::where('student_id', $studentCard->student_number)
            ->orWhere('citizen_id', $studentCard->national_id)
            ->first();
            
        if (!$student) {
            return response()->json(['error' => 'ไม่พบข้อมูลนักเรียนในระบบ'], 404);
        }

        $validatedData = $request->validate([
            // ข้อมูลพื้นฐาน
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
            'class_level' => 'nullable|string|max:10',
            'class_section' => 'nullable|string|max:10',
            'profile_image' => 'nullable|string|max:255',
            'blood_type' => 'nullable|in:A,B,AB,O',
            
            // ข้อมูลที่อยู่
            'address_type' => 'nullable|in:current,permanent',
            'house_number' => 'nullable|string|max:20',
            'village_number' => 'nullable|string|max:10',
            'village_name' => 'nullable|string|max:100',
            'alley' => 'nullable|string|max:100',
            'road' => 'nullable|string|max:100',
            'subdistrict' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            
            // ข้อมูลติดต่อ
            'contact_type' => 'nullable|in:phone,email,line,facebook',
            'contact_value' => 'nullable|string|max:100',
            'is_primary' => 'nullable|boolean',
            
            // ข้อมูลสุขภาพ
            'height' => 'nullable|numeric|min:0|max:300',
            'weight' => 'nullable|numeric|min:0|max:300',
            'allergies' => 'nullable|string',
            'chronic_diseases' => 'nullable|string',
            'medications' => 'nullable|string',
            'vaccination_status' => 'nullable|in:complete,incomplete,unknown',
            'health_notes' => 'nullable|string',
            
            // ข้อมูลผู้ปกครอง
            'guardian_relationship' => 'nullable|in:father,mother,guardian,grandfather,grandmother,uncle,aunt,other',
            'guardian_title_prefix' => 'nullable|string|max:10',
            'guardian_first_name' => 'nullable|string|max:50',
            'guardian_last_name' => 'nullable|string|max:50',
            'guardian_citizen_id' => 'nullable|string|max:13',
            'guardian_occupation' => 'nullable|string|max:100',
            'guardian_workplace' => 'nullable|string|max:200',
            'guardian_income' => 'nullable|numeric|min:0',
            'guardian_is_primary' => 'nullable|boolean',
            
            // ข้อมูลติดต่อผู้ปกครอง
            'guardian_contact_type' => 'nullable|in:phone,email,line,facebook',
            'guardian_contact_value' => 'nullable|string|max:100',
        ]);

        // อัปเดตข้อมูลนักเรียน
        $student->update([
            'title_prefix_th' => $validatedData['title_prefix_th'] ?? $student->title_prefix_th,
            'first_name_th' => $validatedData['first_name_th'] ?? $student->first_name_th,
            'last_name_th' => $validatedData['last_name_th'] ?? $student->last_name_th,
            'title_prefix_en' => $validatedData['title_prefix_en'] ?? $student->title_prefix_en,
            'first_name_en' => $validatedData['first_name_en'] ?? $student->first_name_en,
            'last_name_en' => $validatedData['last_name_en'] ?? $student->last_name_en,
            'nickname' => $validatedData['nickname'] ?? $student->nickname,
            'gender' => $validatedData['gender'] ?? $student->gender,
            'date_of_birth' => $validatedData['date_of_birth'] ?? $student->date_of_birth,
            'nationality' => $validatedData['nationality'] ?? $student->nationality,
            'religion' => $validatedData['religion'] ?? $student->religion,
            'class_level' => $validatedData['class_level'] ?? $student->class_level,
            'class_section' => $validatedData['class_section'] ?? $student->class_section,
            'profile_image' => $validatedData['profile_image'] ?? $student->profile_image,
        ]);

        // อัปเดตข้อมูลที่อยู่
        if ($student->addresses->count() > 0) {
            $address = $student->addresses->first();
            $address->update([
                'address_type' => $validatedData['address_type'] ?? $address->address_type,
                'house_number' => $validatedData['house_number'] ?? $address->house_number,
                'village_number' => $validatedData['village_number'] ?? $address->village_number,
                'village_name' => $validatedData['village_name'] ?? $address->village_name,
                'alley' => $validatedData['alley'] ?? $address->alley,
                'road' => $validatedData['road'] ?? $address->road,
                'subdistrict' => $validatedData['subdistrict'] ?? $address->subdistrict,
                'district' => $validatedData['district'] ?? $address->district,
                'province' => $validatedData['province'] ?? $address->province,
                'postal_code' => $validatedData['postal_code'] ?? $address->postal_code,
            ]);
        } else {
            // สร้างที่อยู่ใหม่ถ้าไม่มี
            $student->addresses()->create([
                'address_type' => $validatedData['address_type'] ?? 'current',
                'house_number' => $validatedData['house_number'],
                'village_number' => $validatedData['village_number'],
                'village_name' => $validatedData['village_name'],
                'alley' => $validatedData['alley'],
                'road' => $validatedData['road'],
                'subdistrict' => $validatedData['subdistrict'],
                'district' => $validatedData['district'],
                'province' => $validatedData['province'],
                'postal_code' => $validatedData['postal_code'],
            ]);
        }

        // อัปเดตข้อมูลติดต่อ
        if ($student->contacts->count() > 0) {
            $contact = $student->contacts->first();
            $contact->update([
                'contact_type' => $validatedData['contact_type'] ?? $contact->contact_type,
                'contact_value' => $validatedData['contact_value'] ?? $contact->contact_value,
                'is_primary' => $validatedData['is_primary'] ?? $contact->is_primary,
            ]);
        } else {
            // สร้างข้อมูลติดต่อใหม่ถ้าไม่มี
            $student->contacts()->create([
                'contact_type' => $validatedData['contact_type'] ?? 'phone',
                'contact_value' => $validatedData['contact_value'],
                'is_primary' => $validatedData['is_primary'] ?? true,
            ]);
        }

        // อัปเดตข้อมูลสุขภาพ
        if ($student->healthInfo) {
            $student->healthInfo->update([
                'height' => $validatedData['height'] ?? $student->healthInfo->height,
                'weight' => $validatedData['weight'] ?? $student->healthInfo->weight,
                'allergies' => $validatedData['allergies'] ?? $student->healthInfo->allergies,
                'chronic_diseases' => $validatedData['chronic_diseases'] ?? $student->healthInfo->chronic_diseases,
                'medications' => $validatedData['medications'] ?? $student->healthInfo->medications,
                'vaccination_status' => $validatedData['vaccination_status'] ?? $student->healthInfo->vaccination_status,
                'health_notes' => $validatedData['health_notes'] ?? $student->healthInfo->health_notes,
            ]);
        } else {
            // สร้างข้อมูลสุขภาพใหม่ถ้าไม่มี
            $student->healthInfo()->create([
                'height' => $validatedData['height'],
                'weight' => $validatedData['weight'],
                'allergies' => $validatedData['allergies'],
                'chronic_diseases' => $validatedData['chronic_diseases'],
                'medications' => $validatedData['medications'],
                'vaccination_status' => $validatedData['vaccination_status'],
                'health_notes' => $validatedData['health_notes'],
            ]);
        }

        // อัปเดตข้อมูลผู้ปกครอง
        if ($student->guardians->count() > 0) {
            $guardian = $student->guardians->first();
            $guardian->update([
                'relationship' => $validatedData['guardian_relationship'] ?? $guardian->relationship,
                'title_prefix' => $validatedData['guardian_title_prefix'] ?? $guardian->title_prefix,
                'first_name' => $validatedData['guardian_first_name'] ?? $guardian->first_name,
                'last_name' => $validatedData['guardian_last_name'] ?? $guardian->last_name,
                'citizen_id' => $validatedData['guardian_citizen_id'] ?? $guardian->citizen_id,
                'occupation' => $validatedData['guardian_occupation'] ?? $guardian->occupation,
                'workplace' => $validatedData['guardian_workplace'] ?? $guardian->workplace,
                'income' => $validatedData['guardian_income'] ?? $guardian->income,
                'is_primary' => $validatedData['guardian_is_primary'] ?? $guardian->is_primary,
            ]);

            // อัปเดตข้อมูลติดต่อผู้ปกครอง
            if ($guardian->contacts->count() > 0) {
                $guardianContact = $guardian->contacts->first();
                $guardianContact->update([
                    'contact_type' => $validatedData['guardian_contact_type'] ?? $guardianContact->contact_type,
                    'contact_value' => $validatedData['guardian_contact_value'] ?? $guardianContact->contact_value,
                ]);
            } else {
                // สร้างข้อมูลติดต่อผู้ปกครองใหม่ถ้าไม่มี
                $guardian->contacts()->create([
                    'contact_type' => $validatedData['guardian_contact_type'] ?? 'phone',
                    'contact_value' => $validatedData['guardian_contact_value'],
                ]);
            }
        } else {
            // สร้างข้อมูลผู้ปกครองใหม่ถ้าไม่มี
            $guardian = $student->guardians()->create([
                'relationship' => $validatedData['guardian_relationship'] ?? 'guardian',
                'title_prefix' => $validatedData['guardian_title_prefix'],
                'first_name' => $validatedData['guardian_first_name'],
                'last_name' => $validatedData['guardian_last_name'],
                'citizen_id' => $validatedData['guardian_citizen_id'],
                'occupation' => $validatedData['guardian_occupation'],
                'workplace' => $validatedData['guardian_workplace'],
                'income' => $validatedData['guardian_income'],
                'is_primary' => $validatedData['guardian_is_primary'] ?? true,
            ]);

            // สร้างข้อมูลติดต่อผู้ปกครอง
            if ($validatedData['guardian_contact_value']) {
                $guardian->contacts()->create([
                    'contact_type' => $validatedData['guardian_contact_type'] ?? 'phone',
                    'contact_value' => $validatedData['guardian_contact_value'],
                ]);
            }
        }

        // รีโหลดข้อมูลพร้อม relationships
        $student = $student->fresh()->load([
            'academicInfo', 
            'addresses', 
            'contacts', 
            'guardians.contacts', 
            'healthInfo'
        ]);

        return back()->with('success', 'อัปเดตข้อมูลสำเร็จแล้ว');
    }

    /**
     * Upload student photos
     */
    public function uploadPhotos(Request $request)
    {
        if (!session('homevisit_authenticated') || session('homevisit_user_type') !== 'student') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg|max:5120',
            'description' => 'nullable|string|max:500'
        ]);

        $studentId = session('homevisit_student_id');
        $student = StudentCard::findOrFail($studentId);

        try {
            $photoPaths = [];
            
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('homevisit/student-photos/' . $studentId, 'public');
                $photoPaths[] = $path;
            }

            // Create or update home visit record for student self-documentation
            $homeVisit = StudentHomeVisit::updateOrCreate(
                [
                    'student_name' => $student->first_name_thai,
                    'class' => $student->class_level,
                    'visit_date' => now()->toDateString(),
                    'visitor_name' => 'นักเรียน (อัปโหลดเอง)',
                ],
                [
                    'photos' => json_encode($photoPaths),
                    'recommendations' => $request->description,
                    'visit_time' => now()->toTimeString(),
                    'visitor_position' => 'นักเรียน',
                    // Basic student info
                    'birth_date' => $student->birth_date ?? now(),
                    'gender' => $student->gender ?? 'ไม่ระบุ',
                    'student_id' => $student->id ?? '',
                    'phone' => $student->student_phone ?? '',
                ]
            );

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
}
