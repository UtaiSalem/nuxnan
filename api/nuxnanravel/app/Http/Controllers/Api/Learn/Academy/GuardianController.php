<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\User;
use App\Services\GuardianService;
use App\Services\GuardianWriteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Guardian Controller - ระบบจัดการผู้ปกครอง
 */
class GuardianController extends Controller
{
    /**
     * Reads use GuardianService. Write methods intentionally remain on student_guardians
     * until G-S4; newly written legacy data is temporarily invisible to these reads.
     */
    public function __construct(private GuardianService $guardianService, private GuardianWriteService $guardianWriteService) {}

    /**
     * Get list of guardians for a student
     */
    public function index(Academy $academy, Student $student)
    {
        // Validate student belongs to academy
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'นักเรียนไม่ได้อยู่ในโรงเรียนนี้',
            ], 404);
        }

        $guardians = $this->guardianService->forStudent($student);

        return response()->json([
            'success' => true,
            'guardians' => $guardians->map(function ($g) {
                return [
                    'id' => $g->id,
                    'guardian_id' => $g->guardian_id,
                    'guardian_type' => $g->guardian_type,
                    'full_name' => $g->full_name,
                    'title_prefix' => $g->title_prefix,
                    'first_name' => $g->first_name,
                    'last_name' => $g->last_name,
                    'relationship' => $g->relationship,
                    'occupation' => $g->occupation,
                    'workplace' => $g->workplace,
                    'is_primary_contact' => $g->is_primary_contact,
                    'is_emergency_contact' => $g->is_emergency_contact,
                    'primary_phone' => $g->primary_phone,
                    'contacts' => $g->guardian?->contacts,
                    'linked_user_id' => $g->guardian?->user_id ?? null,
                ];
            }),
        ], 200);
    }

    /**
     * Create a new guardian
     */
    public function store(Academy $academy, Student $student, Request $request)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'นักเรียนไม่ได้อยู่ในโรงเรียนนี้',
            ], 404);
        }

        $validated = $request->validate([
            'guardian_type' => 'nullable|string|in:father,mother,grandfather,grandmother,uncle,aunt,sibling,other',
            'title_prefix' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'citizen_id' => 'nullable|string|max:20',
            'relationship' => 'nullable|string|max:50',
            'occupation' => 'nullable|string|max:100',
            'workplace' => 'nullable|string|max:200',
            'monthly_income' => 'nullable|numeric|min:0',
            'nationality' => 'nullable|string|max:50',
            'is_primary_contact' => 'nullable|boolean',
            'is_emergency_contact' => 'nullable|boolean',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
        ]);

        try {
            // If setting as primary, unset other primaries
            if ($request->boolean('is_primary_contact')) {
                StudentGuardian::where('student_id', $student->id)
                    ->where('is_primary_contact', true)
                    ->get()
                    ->each(fn (StudentGuardian $otherGuardian) => $this->guardianWriteService->update($otherGuardian, [
                        'is_primary_contact' => false,
                    ]));
            }

            $guardian = $this->guardianWriteService->create($student, [
                'student_id' => $student->id,
                'student_code' => $student->student_id,
                'guardian_type' => $validated['guardian_type'],
                'title_prefix' => $validated['title_prefix'] ?? null,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'citizen_id' => $validated['citizen_id'] ?? null,
                'relationship' => $validated['relationship'] ?? $this->getDefaultRelationship($validated['guardian_type']),
                'occupation' => $validated['occupation'] ?? null,
                'workplace' => $validated['workplace'] ?? null,
                'monthly_income' => $validated['monthly_income'] ?? null,
                'nationality' => $validated['nationality'] ?? 'ไทย',
                'is_primary_contact' => $request->boolean('is_primary_contact'),
                'is_emergency_contact' => $request->boolean('is_emergency_contact'),
                'status' => 'alive',
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'เพิ่มผู้ปกครองเรียบร้อยแล้ว',
                'guardian' => $guardian->load('contacts'),
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a guardian
     */
    public function update(Academy $academy, StudentGuardian $guardian, Request $request)
    {
        // Validate guardian's student belongs to academy
        if (! $guardian->student || $guardian->student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ปกครอง',
            ], 404);
        }

        $validated = $request->validate([
            'guardian_type' => 'sometimes|string|in:father,mother,grandfather,grandmother,uncle,aunt,sibling,other',
            'title_prefix' => 'nullable|string|max:20',
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'citizen_id' => 'nullable|string|max:20',
            'relationship' => 'nullable|string|max:50',
            'occupation' => 'nullable|string|max:100',
            'workplace' => 'nullable|string|max:200',
            'monthly_income' => 'nullable|numeric|min:0',
            'nationality' => 'nullable|string|max:50',
            'is_primary_contact' => 'nullable|boolean',
            'is_emergency_contact' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            // If setting as primary, unset other primaries
            if ($request->boolean('is_primary_contact') && ! $guardian->is_primary_contact) {
                StudentGuardian::where('student_id', $guardian->student_id)
                    ->where('id', '!=', $guardian->id)
                    ->where('is_primary_contact', true)
                    ->get()
                    ->each(fn (StudentGuardian $otherGuardian) => $this->guardianWriteService->update($otherGuardian, [
                        'is_primary_contact' => false,
                    ]));
            }

            $guardian = $this->guardianWriteService->update($guardian, $validated);

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทข้อมูลผู้ปกครองเรียบร้อยแล้ว',
                'guardian' => $guardian->fresh(['contacts']),
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a guardian
     */
    public function destroy(Academy $academy, StudentGuardian $guardian)
    {
        if (! $guardian->student || $guardian->student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ปกครอง',
            ], 404);
        }

        try {
            $this->guardianWriteService->delete($guardian);

            return response()->json([
                'success' => true,
                'message' => 'ลบผู้ปกครองเรียบร้อยแล้ว',
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Link guardian to a user account (for parent login)
     */
    public function linkUser(Academy $academy, StudentGuardian $guardian, Request $request)
    {
        if (! $guardian->student || $guardian->student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ปกครอง',
            ], 404);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        // Add user to academy as parent if not already
        $existingMember = AcademyMember::where('academy_id', $academy->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $existingMember) {
            // Find parent role
            $parentRole = AcademyRole::where('academy_id', $academy->id)
                ->where('name', 'parent')
                ->first();

            AcademyMember::create([
                'academy_id' => $academy->id,
                'user_id' => $user->id,
                'status' => 2, // approved
                'academy_role_id' => $parentRole?->id,
            ]);
        }

        // Update guardian with user_id (need to add column)
        // For now, we store the link in a different way or add the column

        return response()->json([
            'success' => true,
            'message' => 'เชื่อมโยงบัญชีผู้ใช้กับผู้ปกครองเรียบร้อยแล้ว',
        ], 200);
    }

    /**
     * Get all guardians in the academy
     */
    public function getAllGuardians(Academy $academy, Request $request)
    {
        $guardians = $this->guardianService->listForAcademy($academy, $request->only(['search', 'type', 'per_page']));

        return response()->json([
            'success' => true,
            'guardians' => $guardians->map(function ($g) {
                return [
                    'id' => $g->id,
                    'guardian_id' => $g->guardian_id,
                    'guardian_type' => $g->guardian_type,
                    'full_name' => $g->full_name,
                    'relationship' => $g->relationship,
                    'is_primary_contact' => $g->is_primary_contact,
                    'primary_phone' => $g->primary_phone,
                    'student' => $g->student ? [
                        'id' => $g->student->id,
                        'name' => $g->student->first_name_th.' '.$g->student->last_name_th,
                        'student_id' => $g->student->student_id,
                    ] : null,
                ];
            }),
            'pagination' => [
                'current_page' => $guardians->currentPage(),
                'last_page' => $guardians->lastPage(),
                'per_page' => $guardians->perPage(),
                'total' => $guardians->total(),
            ],
        ], 200);
    }

    /**
     * Get guardian statistics
     */
    public function getStatistics(Academy $academy)
    {
        $statistics = $this->guardianService->statisticsForAcademy($academy);

        return response()->json([
            'success' => true,
            'statistics' => [
                ...$statistics,
            ],
        ], 200);
    }

    /**
     * Helper to get default relationship name
     */
    private function getDefaultRelationship(string $type): string
    {
        $relationships = [
            'father' => 'บิดา',
            'mother' => 'มารดา',
            'grandfather' => 'ปู่/ตา',
            'grandmother' => 'ย่า/ยาย',
            'uncle' => 'ลุง/อา',
            'aunt' => 'ป้า/น้า',
            'sibling' => 'พี่/น้อง',
            'other' => 'อื่นๆ',
        ];

        return $relationships[$type] ?? 'ผู้ปกครอง';
    }
}
