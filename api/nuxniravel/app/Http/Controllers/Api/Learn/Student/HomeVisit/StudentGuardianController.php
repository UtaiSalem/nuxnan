<?php

namespace App\Http\Controllers\Api\Learn\Student\HomeVisit;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\GuardianContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StudentGuardianController extends Controller
{
    /**
     * Get student guardian data
     */
    public function show(Request $request, $student_id)
    {
        try {
            // Try to find student by ID first, then by student_id (code)
            $student = Student::find($student_id);
            if (!$student) {
                $student = Student::where('student_id', $student_id)->first();
            }
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลนักเรียน'
                ], 404);
            }

            // Load guardians with contacts using both relationships
            $guardians = StudentGuardian::where('student_id', $student->id)
                ->orWhere('student_code', $student->student_id)
                ->with('contacts')
                ->get();
            
            // Get primary guardian or first guardian
            $guardian = $guardians->first();
            
            if (!$guardian) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'ไม่พบข้อมูลผู้ปกครอง'
                ]);
            }

            // Get primary contact for this guardian
            $contact = $guardian->contacts->where('is_primary', true)->first() 
                     ?? $guardian->contacts->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'guardian' => [
                        'id' => $guardian->id,
                        'guardian_type' => $guardian->guardian_type,
                        'citizen_id' => $guardian->citizen_id,
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
                        'is_emergency_contact' => $guardian->is_emergency_contact
                    ],
                    'contact' => $contact ? [
                        'id' => $contact->id,
                        'contact_type' => $contact->contact_type,
                        'contact_value' => $contact->contact_value,
                        'is_primary' => $contact->is_primary,
                        'is_verified' => $contact->is_verified
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลผู้ปกครอง: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store new guardian data
     */
    public function store(Request $request, $student_id)
    {
        try {
            $student = Student::findOrFail($student_id);
            
            // Validation
            $validator = Validator::make($request->all(), [
                'guardian.guardian_type' => 'required|in:father,mother,guardian,relative',
                'guardian.citizen_id' => 'nullable|string|max:13',
                'guardian.title_prefix' => 'nullable|string|max:20',
                'guardian.first_name' => 'required|string|max:100',
                'guardian.last_name' => 'required|string|max:100',
                'guardian.occupation' => 'nullable|string|max:100',
                'guardian.workplace' => 'nullable|string|max:200',
                'guardian.monthly_income' => 'nullable|numeric|min:0',
                'guardian.relationship' => 'nullable|string|max:50',
                'guardian.is_primary_contact' => 'boolean',
                'guardian.is_emergency_contact' => 'boolean',
                'contact.contact_type' => 'required|in:phone,email,line,facebook,other',
                'contact.contact_value' => 'required|string|max:100',
                'contact.is_primary' => 'boolean'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();
            
            DB::beginTransaction();

            // Create guardian
            $guardian = StudentGuardian::create([
                'student_id' => $student_id,
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
                'nationality' => 'ไทย'
            ]);

            // Create contact for guardian
            $contact = GuardianContact::create([
                'guardian_id' => $guardian->id,
                'contact_type' => $validatedData['contact']['contact_type'],
                'contact_value' => $validatedData['contact']['contact_value'],
                'is_primary' => $validatedData['contact']['is_primary'] ?? true,
                'is_verified' => false
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'guardian' => $guardian->fresh(),
                    'contact' => $contact
                ],
                'message' => 'บันทึกข้อมูลผู้ปกครองสำเร็จ'
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update guardian data
     */
    public function update(Request $request, $student_id)
    {
        try {
            $student = Student::with('guardians.contacts')->findOrFail($student_id);
            
            // Get existing guardian
            $guardian = $student->guardians->first();
            
            if (!$guardian) {
                // If no guardian exists, create new one
                return $this->store($request, $student_id);
            }

            // Validation
            $validator = Validator::make($request->all(), [
                'guardian.guardian_type' => 'required|in:father,mother,guardian,relative',
                'guardian.citizen_id' => 'nullable|string|max:13',
                'guardian.title_prefix' => 'nullable|string|max:20',
                'guardian.first_name' => 'required|string|max:100',
                'guardian.last_name' => 'required|string|max:100',
                'guardian.occupation' => 'nullable|string|max:100',
                'guardian.workplace' => 'nullable|string|max:200',
                'guardian.monthly_income' => 'nullable|numeric|min:0',
                'guardian.relationship' => 'nullable|string|max:50',
                'guardian.is_primary_contact' => 'boolean',
                'guardian.is_emergency_contact' => 'boolean',
                'contact.contact_type' => 'required|in:phone,email,line,facebook,other',
                'contact.contact_value' => 'required|string|max:100',
                'contact.is_primary' => 'boolean'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();
            
            DB::beginTransaction();

            // Update guardian
            $guardian->update([
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
            ]);

            // Get or create primary contact
            $contact = $guardian->contacts->where('is_primary', true)->first() 
                     ?? $guardian->contacts->first();

            if ($contact) {
                // Update existing contact
                $contact->update([
                    'contact_type' => $validatedData['contact']['contact_type'],
                    'contact_value' => $validatedData['contact']['contact_value'],
                    'is_primary' => $validatedData['contact']['is_primary'] ?? true,
                ]);
            } else {
                // Create new contact
                $contact = GuardianContact::create([
                    'guardian_id' => $guardian->id,
                    'contact_type' => $validatedData['contact']['contact_type'],
                    'contact_value' => $validatedData['contact']['contact_value'],
                    'is_primary' => $validatedData['contact']['is_primary'] ?? true,
                    'is_verified' => false
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'guardian' => $guardian->fresh(),
                    'contact' => $contact->fresh()
                ],
                'message' => 'อัปเดตข้อมูลผู้ปกครองสำเร็จ'
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }
}
