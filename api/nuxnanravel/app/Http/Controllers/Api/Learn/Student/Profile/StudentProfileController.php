<?php

namespace App\Http\Controllers\Api\Learn\Student\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\ClassroomMember;
use Illuminate\Support\Facades\Auth;

class StudentProfileController extends Controller
{
    /**
     * Get student profile with all related data
     * 
     * Access control:
     * - Student themselves (via user_id match)
     * - Parent/Guardian (via student_guardians with user_id)
     * - Homeroom teacher (via classroom_members)
     * - School teachers (via academy_members with teacher role)
     * - School admin (via academy_members with admin role)
     */
    public function show(Request $request, $academyId, $studentId)
    {
        $user = Auth::user();

        // Find the academy 
        $academy = Academy::where('id', $academyId)
            ->orWhere('name', $academyId)
            ->first();

        if (!$academy) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสถาบันการศึกษา'
            ], 404);
        }

        // Find the student
        $student = Student::where('id', $studentId)
            ->where('academy_id', $academy->id)
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน'
            ], 404);
        }

        // Check authorization
        $accessLevel = $this->checkAccess($user, $student, $academy);
        if (!$accessLevel) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลนักเรียนนี้'
            ], 403);
        }

        // Load relationships based on access level
        $student->load([
            'academicInfos' => function ($query) {
                $query->orderBy('is_current', 'desc')
                      ->orderBy('academic_year', 'desc');
            },
            'addresses',
            'contacts',
            'guardians',
            'healthInfo',
            'activeClassroom',
            'currentEnrollment',
        ]);

        // Get classroom info
        $classroomInfo = null;
        if ($student->activeClassroom->isNotEmpty()) {
            $classroom = $student->activeClassroom->first();
            $classroomInfo = [
                'id' => $classroom->id,
                'name' => $classroom->name ?? null,
                'grade_level' => $classroom->grade_level ?? $student->class_level,
                'section' => $classroom->section ?? $student->class_section,
                'academic_year' => $classroom->academic_year ?? null,
                'student_number' => $classroom->pivot->student_number ?? null,
            ];
        }

        // Build response data 
        $profileData = [
            'id' => $student->id,
            'student_id' => $student->student_id,
            'citizen_id' => $this->maskCitizenId($student->citizen_id, $accessLevel),
            'title_prefix_th' => $student->title_prefix_th,
            'first_name_th' => $student->first_name_th,
            'last_name_th' => $student->last_name_th,
            'middle_name_th' => $student->middle_name_th,
            'title_prefix_en' => $student->title_prefix_en,
            'first_name_en' => $student->first_name_en,
            'last_name_en' => $student->last_name_en,
            'middle_name_en' => $student->middle_name_en,
            'nickname' => $student->nickname,
            'date_of_birth' => $student->date_of_birth?->format('Y-m-d'),
            'age' => $student->date_of_birth ? $student->date_of_birth->age : null,
            'gender' => $student->gender,
            'gender_text' => $student->gender_text,
            'nationality' => $student->nationality,
            'religion' => $student->religion,
            'profile_image' => $student->profile_image,
            'blood_type' => $student->blood_type,
            'status' => $student->status,
            'enrollment_date' => $student->enrollment_date?->format('Y-m-d'),
            'class_level' => $student->class_level,
            'class_section' => $student->class_section,
        ];

        $response = [
            'success' => true,
            'data' => [
                'student' => $profileData,
                'classroom' => $classroomInfo,
                'academic_info' => $student->academicInfos->map(function ($info) {
                    return [
                        'id' => $info->id,
                        'academic_year' => $info->academic_year,
                        'current_grade' => $info->current_grade,
                        'education_level' => $info->education_level,
                        'current_class' => $info->current_class,
                        'school_name' => $info->school_name,
                        'study_status' => $info->study_status,
                        'is_current' => $info->is_current,
                        'enrollment_date' => $info->enrollment_date,
                        'graduation_date' => $info->graduation_date,
                    ];
                }),
                'addresses' => $student->addresses->map(function ($addr) {
                    return [
                        'id' => $addr->id,
                        'address_type' => $addr->address_type,
                        'house_number' => $addr->house_number,
                        'village_number' => $addr->village_number,
                        'village_name' => $addr->village_name,
                        'alley' => $addr->alley,
                        'road' => $addr->road,
                        'subdistrict' => $addr->subdistrict,
                        'district' => $addr->district,
                        'province' => $addr->province,
                        'postal_code' => $addr->postal_code,
                        'is_current' => $addr->is_current,
                    ];
                }),
                'contacts' => $student->contacts->map(function ($contact) {
                    return [
                        'id' => $contact->id,
                        'contact_type' => $contact->contact_type,
                        'contact_value' => $contact->contact_value,
                        'is_primary' => $contact->is_primary,
                    ];
                }),
                'guardians' => $student->guardians->map(function ($guardian) use ($accessLevel) {
                    $data = [
                        'id' => $guardian->id,
                        'guardian_type' => $guardian->guardian_type,
                        'title_prefix' => $guardian->title_prefix,
                        'first_name' => $guardian->first_name,
                        'last_name' => $guardian->last_name,
                        'relationship' => $guardian->relationship,
                        'occupation' => $guardian->occupation,
                        'is_primary_contact' => $guardian->is_primary_contact,
                        'is_emergency_contact' => $guardian->is_emergency_contact,
                        'status' => $guardian->status,
                    ];

                    // Show sensitive info only for higher access levels
                    if (in_array($accessLevel, ['self', 'parent', 'admin', 'homeroom'])) {
                        $data['citizen_id'] = $guardian->citizen_id;
                        $data['monthly_income'] = $guardian->monthly_income;
                        $data['workplace'] = $guardian->workplace;
                    }

                    return $data;
                }),
                'health_info' => $student->healthInfo ? [
                    'height_cm' => $student->healthInfo->height_cm,
                    'weight_kg' => $student->healthInfo->weight_kg,
                    'blood_type' => $student->healthInfo->blood_type,
                    'rh_factor' => $student->healthInfo->rh_factor,
                    'allergies' => $student->healthInfo->allergies,
                    'chronic_diseases' => $student->healthInfo->chronic_diseases,
                    'medications' => $student->healthInfo->medications,
                    'last_checkup_date' => $student->healthInfo->last_checkup_date,
                ] : null,
                'access_level' => $accessLevel,
                'academy' => [
                    'id' => $academy->id,
                    'name' => $academy->name,
                    'logo' => $academy->logo,
                ],
            ],
        ];

        return response()->json($response);
    }

    /**
     * Get student profile summary (lightweight version for lists/cards)
     */
    public function summary(Request $request, $academyId, $studentId)
    {
        $user = Auth::user();

        $academy = Academy::where('id', $academyId)
            ->orWhere('name', $academyId)
            ->first();

        if (!$academy) {
            return response()->json(['success' => false, 'message' => 'ไม่พบสถาบัน'], 404);
        }

        $student = Student::where('id', $studentId)
            ->where('academy_id', $academy->id)
            ->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'ไม่พบนักเรียน'], 404);
        }

        $accessLevel = $this->checkAccess($user, $student, $academy);
        if (!$accessLevel) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'full_name' => trim("{$student->title_prefix_th} {$student->first_name_th} {$student->last_name_th}"),
                'nickname' => $student->nickname,
                'class_level' => $student->class_level,
                'class_section' => $student->class_section,
                'profile_image' => $student->profile_image,
                'gender' => $student->gender,
                'status' => $student->status,
            ],
        ]);
    }

    /**
     * Check user's access level to student data
     * Returns access level string or null if no access
     */
    private function checkAccess($user, Student $student, Academy $academy): ?string
    {
        if (!$user) {
            return null;
        }

        // 1. Student themselves
        if ($student->user_id && $student->user_id === $user->id) {
            return 'self';
        }

        // 2. Check if user is academy member
        $academyMember = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->first();

        if (!$academyMember) {
            return null;
        }

        // 3. Academy admin/owner
        if (in_array($academyMember->role, ['admin', 'owner', 'director'])) {
            return 'admin';
        }

        // 4. Check if teacher is homeroom teacher of student's classroom
        if (in_array($academyMember->role, ['teacher', 'co_teacher'])) {
            $isHomeroom = ClassroomMember::whereHas('classroom', function ($q) use ($student) {
                $q->whereHas('classroomStudents', function ($sq) use ($student) {
                    $sq->where('student_id', $student->id)
                       ->where('status', 'active');
                });
            })
            ->where('user_id', $user->id)
            ->where('role', 'teacher')
            ->where('is_active', true)
            ->exists();

            if ($isHomeroom) {
                return 'homeroom';
            }

            // 5. School teacher (same academy)
            return 'teacher';
        }

        // 6. Parent - check if user is linked as guardian
        // Note: Currently guardians don't have user_id linkage
        // This will work once guardian-user linking is implemented
        $isParent = $student->guardians()
            ->where(function ($q) use ($user) {
                $q->where('citizen_id', $user->citizen_id ?? '__none__');
            })
            ->exists();

        if ($isParent) {
            return 'parent';
        }

        // 7. Regular academy member (student role) - limited access
        if ($academyMember->role === 'student') {
            return null; // Students can't view other students' profiles
        }

        return null;
    }

    /**
     * Mask citizen ID based on access level
     */
    private function maskCitizenId(?string $citizenId, string $accessLevel): ?string
    {
        if (!$citizenId) {
            return null;
        }

        // Full access for self, parent, admin, homeroom
        if (in_array($accessLevel, ['self', 'parent', 'admin', 'homeroom'])) {
            return $citizenId;
        }

        // Masked for teachers
        $digits = preg_replace('/\D/', '', $citizenId);
        if (strlen($digits) !== 13) {
            return '***-****-*****-**-*';
        }

        return $digits[0] . '-****-*****-**-' . $digits[12];
    }
}
