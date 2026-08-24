<?php

namespace App\Http\Resources;

use App\Models\AcademyMember;
use App\Services\GuardianAccessService;
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
                $hasFullAccess = AcademyMember::where('user_id', $user->id)
                    ->where('academy_id', $this->academy_id)
                    ->whereIn('role', ['admin', 'teacher', 'director'])
                    ->exists();
            }
        }

        // Mask citizen ID if not full access. Format keeps Thai national-id grouping: 1-XXXX-XXXXX-XX-X
        $citizenId = $this->citizen_id;
        if (! $hasFullAccess && $citizenId) {
            $digits = preg_replace('/\D/', '', $citizenId);
            if (strlen($digits) === 13) {
                $citizenId = $digits[0].'-XXXX-XXXXX-XX-'.substr($digits, -1);
            } elseif (strlen($digits) >= 4) {
                $citizenId = str_repeat('X', max(0, strlen($digits) - 4)).substr($digits, -4);
            } else {
                $citizenId = null;
            }
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
            'guardians' => $this->whenLoaded('guardians', fn () => app(GuardianAccessService::class)
                ->canViewSensitive($request->user(), $this->resource)
                    ? $this->guardians
                    : app(GuardianAccessService::class)->hideSensitive($this->guardians)),
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
