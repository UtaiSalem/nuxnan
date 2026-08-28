<?php

namespace App\Http\Controllers\Api\Learn\Student\Profile;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\ClassroomMember;
use App\Models\GuardianAccountRequest;
use App\Models\Student;
use App\Services\GuardianAccessService;
use App\Services\GuardianAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentProfileController extends Controller
{
    /**
     * Get a student profile by academy and student ID.
     *
     * Access control:
     * - Student themselves (via user_id match)
     * - Parent/Guardian (via the account link on the guardian person)
     * - Homeroom teacher (via classroom_members)
     * - School teachers (via academy_members with teacher role)
     * - School admin (via academy_members with admin role)
     */
    public function show(Request $request, $academyId, $studentId)
    {
        $user = Auth::user();

        $academy = $this->findAcademy($academyId);
        if (! $academy) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสถาบันการศึกษา',
            ], 404);
        }

        $student = Student::where('id', $studentId)
            ->where('academy_id', $academy->id)
            ->first();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน',
            ], 404);
        }

        $accessLevel = $this->checkAccess($user, $student, $academy);
        if (! $accessLevel) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลนักเรียนนี้',
            ], 403);
        }

        return $this->buildProfileResponse($student, $academy, $accessLevel);
    }

    /**
     * Get the current authenticated user's own student profile.
     *
     * Route: GET /api/academies/{academy}/students/me/profile
     *
     * No explicit access check needed — access is implicitly "self".
     * Returns 404 with code STUDENT_NOT_LINKED when the user has no linked
     * student record in this academy (e.g. parent, teacher, or pending member).
     */
    public function myProfile(Request $request, $academyId)
    {
        $user = Auth::user();

        $academy = $this->findAcademy($academyId);
        if (! $academy) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสถาบันการศึกษา',
            ], 404);
        }

        $student = Student::where('academy_id', $academy->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'บัญชีของคุณยังไม่ได้เชื่อมกับข้อมูลนักเรียนในโรงเรียนนี้ กรุณาติดต่อครูประจำชั้น',
                'code' => 'STUDENT_NOT_LINKED',
            ], 404);
        }

        return $this->buildProfileResponse($student, $academy, 'self');
    }

    /**
     * Get student profile summary (lightweight version for lists/cards)
     */
    public function summary(Request $request, $academyId, $studentId)
    {
        $user = Auth::user();

        $academy = $this->findAcademy($academyId);
        if (! $academy) {
            return response()->json(['success' => false, 'message' => 'ไม่พบสถาบัน'], 404);
        }

        $student = Student::where('id', $studentId)
            ->where('academy_id', $academy->id)
            ->first();

        if (! $student) {
            return response()->json(['success' => false, 'message' => 'ไม่พบนักเรียน'], 404);
        }

        $accessLevel = $this->checkAccess($user, $student, $academy);
        if (! $accessLevel) {
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
     * Summary for the current authenticated user's own student record.
     */
    public function mySummary(Request $request, $academyId)
    {
        $user = Auth::user();

        $academy = $this->findAcademy($academyId);
        if (! $academy) {
            return response()->json(['success' => false, 'message' => 'ไม่พบสถาบัน'], 404);
        }

        $student = Student::where('academy_id', $academy->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียนที่เชื่อมโยง',
                'code' => 'STUDENT_NOT_LINKED',
            ], 404);
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

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Find academy by ID or name.
     */
    private function findAcademy($academyId): ?Academy
    {
        return Academy::where('id', $academyId)
            ->orWhere('name', $academyId)
            ->first();
    }

    /**
     * Build the full profile response for a given student, academy, and access level.
     */
    private function buildProfileResponse(Student $student, Academy $academy, string $accessLevel)
    {
        // Load relationships
        $student->load([
            'academicInfos' => function ($query) {
                $query->orderBy('is_current', 'desc')
                    ->orderBy('academic_year', 'desc');
            },
            'addresses',
            'contacts',
            'guardianLinks.guardian',
            'guardianLinks.guardian.user:id,name',
            'healthInfo',
            'activeClassroom',
            'currentEnrollment',
            'studentCard',
            'homeVisits' => function ($query) {
                $query->orderBy('visit_date', 'desc')->limit(5);
            },
        ]);

        // Classroom info
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

        // Profile data
        $profileData = [
            'id' => $student->id,
            'academy_id' => $student->academy_id,
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

        // 'parent' is the guardian looking at their own record, so it keeps the fields it always had.
        $showSensitive = $accessLevel === 'parent'
            || app(GuardianAccessService::class)->canViewSensitive(Auth::user(), $student);

        // A guardian this student attached from someone else's record stays masked until staff verifies it.
        $blockedGuardianIds = app(GuardianAccessService::class)->unverifiedSelfAppointedIds($student);

        // Account-link state for the guardian cards (G-S12d). One query for the whole student,
        // not one per guardian row.
        $pendingAccountGuardianIds = GuardianAccountRequest::query()
            ->where('student_id', $student->id)
            ->pending()
            ->pluck('guardian_id')
            ->filter()
            ->all();

        if ($showSensitive && $student->guardianLinks->isNotEmpty()) {
            app(GuardianAuditLogger::class)->sensitiveViewed(Auth::user(), $student);
        }

        return response()->json([
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
                'guardians' => $student->guardianLinks->map(function ($link) use ($showSensitive, $blockedGuardianIds, $pendingAccountGuardianIds) {
                    $data = [
                        // `id` is the link row now. Nothing posts it back — the guardian write routes
                        // take the student, not a guardian id — so this is a list key only.
                        'id' => $link->id,
                        'guardian_id' => $link->guardian_id,
                        'guardian_type' => $link->guardian_type,
                        'title_prefix' => $link->title_prefix,
                        'first_name' => $link->first_name,
                        'last_name' => $link->last_name,
                        'relationship' => $link->relationship,
                        'occupation' => $link->occupation,
                        'workplace' => $link->workplace,
                        'is_primary_contact' => $link->is_primary_contact,
                        'is_emergency_contact' => $link->is_emergency_contact,
                        'status' => $link->status,
                        'link_id' => $link->id,
                        'appointed_by_role' => $link->appointed_by_role,
                        'verified_at' => $link->verified_at,
                        'is_verified' => $link->verified_at !== null,
                        'linked_user_id' => $link->guardian?->user_id,
                        'linked_user_name' => $link->guardian?->user?->name,
                        'has_pending_account_request' => in_array($link->guardian_id, $pendingAccountGuardianIds, true),
                    ];

                    if ($showSensitive && ! app(GuardianAccessService::class)->isBlockedGuardianRow($blockedGuardianIds, $link)) {
                        $data['citizen_id'] = $link->citizen_id;
                        $data['monthly_income'] = $link->monthly_income;
                    }

                    return $data;
                })->values(),
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
                'student_card' => $this->buildStudentCardSection($student),
                'home_visit' => $this->buildHomeVisitSection($student, $accessLevel),
                'school_activity' => $this->buildSchoolActivitySection($student, $academy),
            ],
        ]);
    }

    /**
     * Check user's access level to student data.
     * Returns access level string or null if no access.
     */
    private function checkAccess($user, Student $student, Academy $academy): ?string
    {
        if (! $user) {
            return null;
        }

        // 1. Student themselves
        if ($student->user_id && $student->user_id === $user->id) {
            return 'self';
        }

        // 2. Academy owner / super admin — they usually have no academy_members row
        if ($academy->isAdmin($user)) {
            return 'admin';
        }

        // 3. Check if user is academy member
        $academyMember = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', $user->id)
            ->where('status', AcademyMember::STATUS_APPROVED)
            ->first();

        if (! $academyMember) {
            return null;
        }

        // 4. Academy admin/owner
        if (in_array($academyMember->role, ['admin', 'owner', 'director'])) {
            return 'admin';
        }

        // 5. Check if teacher is homeroom teacher/co-teacher of student's classroom
        if (in_array($academyMember->role, ['teacher', 'co_teacher'])) {
            if (ClassroomMember::isHomeroomStaffOf($user->id, $student)) {
                return 'homeroom';
            }

            return 'teacher';
        }

        // 6. Parent - check if user is linked as guardian
        if (app(GuardianAccessService::class)->isGuardianOf($user, $student)) {
            return 'parent';
        }

        // 7. Regular academy member (student role) - limited access
        if ($academyMember->role === 'student') {
            return null;
        }

        return null;
    }

    /**
     * Build the student_card section of the profile response.
     */
    private function buildStudentCardSection(Student $student): array
    {
        $card = $student->getRelationValue('studentCard');

        if (! $card) {
            return [
                'exists' => false,
                'id' => null,
                'card_number' => null,
                'issued_at' => null,
                'expires_at' => null,
                'photo_status' => 'missing',
                'preview_url' => null,
                'match_strategy' => null,
            ];
        }

        return [
            'exists' => true,
            'id' => $card->id,
            'card_number' => $card->student_number,
            'issued_at' => $card->card_issue_date,
            'expires_at' => $card->card_expiry_date,
            'photo_status' => $card->profile_image ? 'approved' : 'missing',
            'preview_url' => $card->profile_image ? asset('storage/'.$card->profile_image) : null,
            'match_strategy' => 'fk',
        ];
    }

    /**
     * Build the home_visit section of the profile response.
     */
    private function buildHomeVisitSection(Student $student, string $accessLevel): array
    {
        $visits = $student->getRelationValue('homeVisits') ?? collect();

        $latest = $visits->first();
        $nextScheduled = $visits->first(fn ($visit) => in_array($visit->visit_status, ['scheduled', 'pending'], true));

        return [
            'total_visits' => $student->homeVisits()->count(),
            'latest' => $latest ? [
                'id' => $latest->id,
                'visited_at' => $latest->visit_date?->toDateString(),
                'status' => $latest->visit_status,
                'visitor_name' => $latest->visitor_name,
            ] : null,
            'next_scheduled' => $nextScheduled ? [
                'id' => $nextScheduled->id,
                'scheduled_at' => ($nextScheduled->next_visit ?? $nextScheduled->visit_date)?->toDateString(),
            ] : null,
            'recent' => $visits->take(5)->map(fn ($v) => [
                'id' => $v->id,
                'visited_at' => $v->visit_date?->toDateString(),
                'status' => $v->visit_status,
                'visitor_name' => $v->visitor_name,
                'observations' => in_array($accessLevel, ['admin', 'homeroom', 'teacher']) ? $v->observations : null,
            ])->values(),
        ];
    }

    /**
     * Build the school_activity section of the profile response.
     */
    private function buildSchoolActivitySection(Student $student, Academy $academy): array
    {
        $member = AcademyMember::where('academy_id', $academy->id)
            ->where('student_id', $student->id)
            ->first();

        return [
            'joined_at' => $member?->enrollment_date ?? $member?->created_at?->toDateString(),
            'member_code' => $member?->member_code,
            'last_active_at' => $student->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Mask citizen ID based on access level.
     */
    private function maskCitizenId(?string $citizenId, string $accessLevel): ?string
    {
        if (! $citizenId) {
            return null;
        }

        if (in_array($accessLevel, ['self', 'parent', 'admin', 'homeroom'])) {
            return $citizenId;
        }

        $digits = preg_replace('/\D/', '', $citizenId);
        if (strlen($digits) !== 13) {
            return '***-****-*****-**-*';
        }

        return $digits[0].'-****-*****-**-'.$digits[12];
    }
}
