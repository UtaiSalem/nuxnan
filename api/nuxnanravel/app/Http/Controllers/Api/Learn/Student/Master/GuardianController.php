<?php

namespace App\Http\Controllers\Api\Learn\Student\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateGuardianRequest;
use App\Models\Academy;
use App\Models\GuardianContact;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Services\GuardianService;
use App\Traits\HandlesStudentUpdates;
use Illuminate\Support\Facades\DB;

class GuardianController extends Controller
{
    /**
     * Reads use GuardianService; store/update/destroy remain on student_guardians until G-S4.
     * Newly written legacy data is temporarily invisible during this migration window.
     */
    use HandlesStudentUpdates;

    public function __construct(private GuardianService $guardianService) {}

    /**
     * Get student guardian data
     */
    public function show(Academy $academy, Student $student)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        $this->authorize('update', $student);

        try {
            $guardians = $this->guardianService->forStudent($student);

            // Get primary guardian or first guardian
            $guardian = $guardians->first();

            if (! $guardian) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'ไม่พบข้อมูลผู้ปกครอง',
                ]);
            }

            // Get primary contact for this guardian
            $contact = $guardian->guardian?->contacts->where('is_primary', true)->first()
                     ?? $guardian->guardian?->contacts->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'guardian' => [
                        'id' => $guardian->id,
                        'guardian_id' => $guardian->guardian_id,
                        'guardian_type' => $guardian->guardian_type,
                        'citizen_id' => $guardian->guardian?->citizen_id,
                        'title_prefix' => $guardian->title_prefix,
                        'first_name' => $guardian->first_name,
                        'last_name' => $guardian->last_name,
                        'full_name' => $guardian->full_name,
                        'occupation' => $guardian->occupation,
                        'workplace' => $guardian->workplace,
                        'monthly_income' => $guardian->monthly_income,
                        'relationship' => $guardian->relationship,
                        'status' => $guardian->status,
                        'nationality' => $guardian->nationality,
                        'is_primary_contact' => $guardian->is_primary_contact,
                        'is_emergency_contact' => $guardian->is_emergency_contact,
                    ],
                    'contact' => $contact ? [
                        'id' => $contact->id,
                        'contact_type' => $contact->contact_type,
                        'contact_value' => $contact->contact_value,
                        'is_primary' => $contact->is_primary,
                        'is_verified' => $contact->is_verified,
                    ] : null,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลผู้ปกครอง: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store new guardian data
     */
    public function store(UpdateGuardianRequest $request, Academy $academy, Student $student)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        $this->authorize('update', $student);

        try {
            $validatedData = $request->validated();

            DB::beginTransaction();

            // Create guardian (always blacklist under normal settings, so owner goes pending)
            $changeRequest = $this->applyUpdate($student, 'StudentGuardian', null, 'guardian.create', $validatedData);
            if ($changeRequest) {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'ส่งคำขอเพิ่มข้อมูลผู้ปกครองแล้ว รอการอนุมัติ',
                    'needs_approval' => true,
                ]);
            }

            // Create guardian directly
            $guardian = StudentGuardian::create([
                'student_id' => $student->id,
                'student_code' => $student->student_id,
                'guardian_type' => $validatedData['guardian']['guardian_type'],
                'citizen_id' => $validatedData['guardian']['citizen_id'] ?? null,
                'title_prefix' => $validatedData['guardian']['title_prefix'] ?? null,
                'first_name' => $validatedData['guardian']['first_name'],
                'last_name' => $validatedData['guardian']['last_name'],
                'occupation' => $validatedData['guardian']['occupation'] ?? null,
                'workplace' => $validatedData['guardian']['workplace'] ?? null,
                'monthly_income' => $validatedData['guardian']['monthly_income'] ?? null,
                'relationship' => $validatedData['guardian']['relationship'] ?? null,
                'is_primary_contact' => $validatedData['guardian']['is_primary_contact'] ?? false,
                'is_emergency_contact' => $validatedData['guardian']['is_emergency_contact'] ?? false,
                'status' => 'alive',
                'nationality' => 'ไทย',
            ]);

            // Create contact for guardian
            $contact = GuardianContact::create([
                'guardian_id' => $guardian->id,
                'contact_type' => $validatedData['contact']['contact_type'],
                'contact_value' => $validatedData['contact']['contact_value'],
                'is_primary' => $validatedData['contact']['is_primary'] ?? true,
                'is_verified' => false,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'guardian' => $guardian->fresh(),
                    'contact' => $contact,
                ],
                'message' => 'บันทึกข้อมูลผู้ปกครองสำเร็จ',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update guardian data
     */
    public function update(UpdateGuardianRequest $request, Academy $academy, Student $student)
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลนักเรียนไม่ได้อยู่ในสถาบันการศึกษานี้',
            ], 403);
        }

        $this->authorize('update', $student);

        try {
            // Load existing guardian
            $guardian = StudentGuardian::where('student_id', $student->id)->first();

            if (! $guardian) {
                // If no guardian exists, create new one
                return $this->store($request, $academy, $student);
            }

            $validatedData = $request->validated();

            DB::beginTransaction();

            // Route through approval flow
            $guardianFields = [
                'guardian_type' => $validatedData['guardian']['guardian_type'],
                'citizen_id' => $validatedData['guardian']['citizen_id'] ?? null,
                'title_prefix' => $validatedData['guardian']['title_prefix'] ?? null,
                'first_name' => $validatedData['guardian']['first_name'],
                'last_name' => $validatedData['guardian']['last_name'],
                'occupation' => $validatedData['guardian']['occupation'] ?? null,
                'workplace' => $validatedData['guardian']['workplace'] ?? null,
                'monthly_income' => $validatedData['guardian']['monthly_income'] ?? null,
                'relationship' => $validatedData['guardian']['relationship'] ?? null,
                'is_primary_contact' => $validatedData['guardian']['is_primary_contact'] ?? false,
                'is_emergency_contact' => $validatedData['guardian']['is_emergency_contact'] ?? false,
            ];
            $guardianResult = $this->processFieldUpdates($student, $guardian, 'StudentGuardian', 'guardian', $guardianFields);

            // Get or create primary contact
            $contact = $guardian->contacts->where('is_primary', true)->first()
                     ?? $guardian->contacts->first();

            // Let's also check approval for contacts if needed, but per-spec contacts update directly or can be updated directly.
            // As contact is guardian's contact, we can update directly as per current behavior or check.
            if ($contact) {
                $contact->update([
                    'contact_type' => $validatedData['contact']['contact_type'],
                    'contact_value' => $validatedData['contact']['contact_value'],
                    'is_primary' => $validatedData['contact']['is_primary'] ?? true,
                ]);
            } else {
                $contact = GuardianContact::create([
                    'guardian_id' => $guardian->id,
                    'contact_type' => $validatedData['contact']['contact_type'],
                    'contact_value' => $validatedData['contact']['contact_value'],
                    'is_primary' => $validatedData['contact']['is_primary'] ?? true,
                    'is_verified' => false,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'guardian' => $guardian->fresh(),
                    'contact' => $contact->fresh(),
                ],
                'pending_fields' => $guardianResult['pending'] ?? [],
                'message' => empty($guardianResult['pending'])
                    ? 'อัปเดตข้อมูลผู้ปกครองสำเร็จ'
                    : 'ส่งคำขอแก้ไขข้อมูลผู้ปกครองรอการอนุมัติแล้ว',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: '.$e->getMessage(),
            ], 500);
        }
    }
}
