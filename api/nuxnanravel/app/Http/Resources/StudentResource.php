<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        
        // Determine if user has full access to this student's data
        $hasFullAccess = false;
        if ($user) {
            if ($user->id === $this->user_id) {
                $hasFullAccess = true;
            } else {
                // Check if user is staff in the student's academy
                $hasFullAccess = \App\Models\AcademyMember::where('user_id', $user->id)
                    ->where('academy_id', $this->academy_id)
                    ->whereIn('role', ['admin', 'teacher', 'director'])
                    ->exists();
            }
        }

        // Mask citizen ID if not full access
        $citizenId = $this->citizen_id;
        if (!$hasFullAccess && $citizenId && strlen($citizenId) >= 13) {
            $citizenId = substr($citizenId, 0, 3) . 'XXXXXXX' . substr($citizenId, -3);
        }

        return [
            'id' => $this->id,
            'academy_id' => $this->academy_id,
            'user_id' => $this->user_id,
            'student_id' => $this->student_id,
            'citizen_id' => $citizenId,
            'title_prefix_th' => $this->title_prefix_th,
            'first_name_th' => $this->first_name_th,
            'last_name_th' => $this->last_name_th,
            'middle_name_th' => $this->middle_name_th,
            'title_prefix_en' => $this->title_prefix_en,
            'first_name_en' => $this->first_name_en,
            'last_name_en' => $this->last_name_en,
            'middle_name_en' => $this->middle_name_en,
            'nickname' => $this->nickname,
            'full_name_th' => "{$this->title_prefix_th}{$this->first_name_th} {$this->last_name_th}",
            'full_name_en' => "{$this->title_prefix_en} {$this->first_name_en} {$this->last_name_en}",
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'gender_text' => $this->gender_text,
            'nationality' => $this->nationality,
            'religion' => $this->religion,
            'profile_image' => $this->profile_image,
            'status' => $this->status,
            'enrollment_date' => $this->enrollment_date,
            'class_level' => $this->class_level,
            'class_section' => $this->class_section,
            
            // Sub-tables relationships (Phase 2 shape completion)
            'academic' => $this->whenLoaded('currentAcademicInfo'),
            'card' => $this->whenLoaded('studentCard'),
            'addresses' => $this->whenLoaded('addresses'),
            'contacts' => $this->whenLoaded('contacts'),
            'guardians' => $this->whenLoaded('guardians'),
            'health' => $this->whenLoaded('healthInfo'),
            'documents' => $this->whenLoaded('documents'),
            
            // Permissions metadata for frontend
            'permissions' => [
                'can_edit' => $hasFullAccess || ($user && $user->id === $this->user_id),
                'can_view_full' => $hasFullAccess,
            ],
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
