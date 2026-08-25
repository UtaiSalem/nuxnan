<?php

namespace App\Http\Controllers\Api\Learn\Student\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateGuardianRequest;
use App\Models\Academy;
use App\Models\Student;
use App\Models\StudentGuardianLink;
use App\Services\GuardianAccessService;
use App\Services\GuardianAuditLogger;
use App\Services\GuardianService;
use App\Services\GuardianWriteService;
use App\Traits\HandlesStudentUpdates;
use Illuminate\Support\Facades\DB;

class GuardianController extends Controller
{
    /**
     * Reads and writes both run on the person model (guardians + student_guardian_links).
     */
    use HandlesStudentUpdates;

    public function __construct(private GuardianService $guardianService, private GuardianWriteService $guardianWriteService) {}

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

        $this->authorize('viewGuardians', $student);

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

            $guardianData = [
                'id' => $guardian->id,
                'guardian_id' => $guardian->guardian_id,
                'guardian_type' => $guardian->guardian_type,
            ];

            $access = app(GuardianAccessService::class);
            $showSensitive = $access->canViewSensitive(auth()->user(), $student)
                && ! $access->blocksSensitiveRow(auth()->user(), $student, $guardian);

            if ($showSensitive) {
                $guardianData['citizen_id'] = $guardian->guardian?->citizen_id;
            }

            $guardianData['title_prefix'] = $guardian->title_prefix;
            $guardianData['first_name'] = $guardian->first_name;
            $guardianData['last_name'] = $guardian->last_name;
            $guardianData['full_name'] = $guardian->full_name;
            $guardianData['occupation'] = $guardian->occupation;
            $guardianData['workplace'] = $guardian->workplace;

            if ($showSensitive) {
                $guardianData['monthly_income'] = $guardian->monthly_income;
            }

            $guardianData['relationship'] = $guardian->relationship;
            $guardianData['status'] = $guardian->status;
            $guardianData['nationality'] = $guardian->nationality;
            $guardianData['is_primary_contact'] = $guardian->is_primary_contact;
            $guardianData['is_emergency_contact'] = $guardian->is_emergency_contact;

            if ($showSensitive) {
                app(GuardianAuditLogger::class)->sensitiveViewed(auth()->user(), $student);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'guardian' => $guardianData,
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

        $this->authorize('manageGuardians', $student);

        try {
            $validatedData = $request->validated();

            $access = app(GuardianAccessService::class);
            if (! $access->canManageSensitive(auth()->user(), $student)) {
                $blocked = $access->changedSensitiveFields(
                    $validatedData['guardian'] ?? [],
                    null
                );

                if ($blocked !== []) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ไม่มีสิทธิ์แก้ไขข้อมูลอ่อนไหวของผู้ปกครอง: '.implode(', ', $blocked),
                    ], 403);
                }
            }

            DB::beginTransaction();

            // Create guardian (always blacklist under normal settings, so owner goes pending)
            $changeRequest = $this->applyUpdate($student, 'StudentGuardianLink', null, 'guardian.create', $validatedData);
            if ($changeRequest) {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'ส่งคำขอเพิ่มข้อมูลผู้ปกครองแล้ว รอการอนุมัติ',
                    'needs_approval' => true,
                ]);
            }

            // Create guardian directly
            $guardian = $this->guardianWriteService->create($student, $validatedData);
            $contact = $guardian->fresh()->contacts()->first();

            DB::commit();

            app(GuardianAuditLogger::class)->created($student, $guardian, $validatedData['guardian'] ?? []);

            $access = app(GuardianAccessService::class);
            $responseGuardian = $this->guardianService->linkPayload(
                $guardian->fresh()->load('guardian.contacts'),
                $access->canViewSensitive(auth()->user(), $student),
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'guardian' => $responseGuardian,
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

        $this->authorize('manageGuardians', $student);

        try {
            // Load existing guardian
            $guardian = StudentGuardianLink::where('student_id', $student->id)->orderBy('id')->first();

            if (! $guardian) {
                // If no guardian exists, create new one
                return $this->store($request, $academy, $student);
            }

            $validatedData = $request->validated();

            $access = app(GuardianAccessService::class);
            if (! $access->canManageSensitive(auth()->user(), $student)) {
                $blocked = $access->changedSensitiveFields(
                    $validatedData['guardian'] ?? [],
                    $guardian
                );

                if ($blocked !== []) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ไม่มีสิทธิ์แก้ไขข้อมูลอ่อนไหวของผู้ปกครอง: '.implode(', ', $blocked),
                    ], 403);
                }
            }

            DB::beginTransaction();

            // Guardian edits are applied directly. Unlike store(), this path never went through
            // applyUpdate(), so no change request is ever created here and pending_fields below is
            // always empty — the key stays for parity with the other profile sections.
            $guardian = $this->guardianWriteService->update($guardian, $validatedData);

            // GuardianWriteService::update() already wrote $validatedData['contact'] against the
            // person. Read it back so the response still carries the primary contact.
            $contact = $guardian->contacts()->where('is_primary', true)->first()
                     ?? $guardian->contacts()->first();

            DB::commit();

            app(GuardianAuditLogger::class)->updated($student, $guardian, $validatedData['guardian'] ?? []);

            $access = app(GuardianAccessService::class);
            $responseGuardian = $this->guardianService->linkPayload(
                $guardian->fresh()->load('guardian.contacts'),
                $access->canViewSensitive(auth()->user(), $student),
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'guardian' => $responseGuardian,
                    'contact' => $contact?->fresh(),
                ],
                'pending_fields' => [],
                'message' => 'อัปเดตข้อมูลผู้ปกครองสำเร็จ',
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
