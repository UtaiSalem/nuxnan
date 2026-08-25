<?php

namespace App\Http\Resources\Learn\Academy\Enrollment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentIntakeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $student = $this->resource['student'];

        return [
            'student' => (new StudentSummaryResource($student))->toArray($request) + [
                'citizen_id' => $student->citizen_id,
                'account_status' => $student->account_status,
                'enrollment_date' => $student->enrollment_date?->toDateString(),
            ],
            'membership' => [
                'id' => $this->resource['membership']->id,
                'status' => $this->resource['membership']->status,
                'role' => $this->resource['membership']->role,
                'academy_role_id' => $this->resource['membership']->academy_role_id,
            ],
            'enrollment' => (new ClassroomStudentResource($this->resource['enrollment']))->toArray($request),
            'academic_info' => $student->currentAcademicInfo ? [
                'id' => $student->currentAcademicInfo->id,
                'academic_year' => $student->currentAcademicInfo->academic_year,
                'classroom_id' => $student->currentAcademicInfo->classroom_id,
                'study_status' => $student->currentAcademicInfo->study_status,
                'is_current' => $student->currentAcademicInfo->is_current,
            ] : null,
            'guardians' => $student->guardianLinks->map(fn ($link): array => [
                'id' => $link->id,
                'guardian_id' => $link->guardian_id,
                'guardian_type' => $link->guardian_type,
                'first_name' => $link->first_name,
                'last_name' => $link->last_name,
                'relationship' => $link->relationship,
                'is_primary_contact' => $link->is_primary_contact,
                'is_emergency_contact' => $link->is_emergency_contact,
                'contacts' => $link->guardian?->contacts->map(fn ($contact): array => [
                    'id' => $contact->id,
                    'contact_type' => $contact->contact_type,
                    'contact_value' => $contact->contact_value,
                    'is_primary' => $contact->is_primary,
                ])->values()->all() ?? [],
            ])->values()->all(),
        ];
    }
}
