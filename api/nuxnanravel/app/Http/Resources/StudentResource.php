<?php

namespace App\Http\Resources;

use App\Models\AcademyMember;
use App\Models\GuardianAccountRequest;
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
            // Built by hand rather than serialized: on a link row the person's fields are
            // accessors, and accessors are not in the model's attribute bag, so handing the
            // model to the serializer would emit neither the names nor the sensitive pair —
            // and makeHidden() would have nothing to hide.
            'guardians' => $this->whenLoaded('guardianLinks', function () use ($request) {
                $access = app(GuardianAccessService::class);
                $showSensitive = $access->canViewSensitive($request->user(), $this->resource);

                // The unverified-appointment gate closes only for the student reading their own
                // record; staff who cleared guardians.sensitive.view already passed a permission
                // check. That was maskUnverifiedSelfAppointments()'s rule and it is kept as-is —
                // skipping the lookup entirely for every other viewer.
                $selfView = $request->user() !== null
                    && $this->resource->user_id !== null
                    && $this->resource->user_id === $request->user()->id;
                $blockedGuardianIds = $selfView
                    ? $access->unverifiedSelfAppointedIds($this->resource)
                    : ['link' => [], 'person' => [], 'legacy' => []];

                $pendingGuardianIds = GuardianAccountRequest::query()
                    ->where('student_id', $this->resource->id)
                    ->pending()
                    ->pluck('guardian_id')
                    ->filter()
                    ->all();

                return $this->guardianLinks->map(function ($link) use ($access, $showSensitive, $blockedGuardianIds, $pendingGuardianIds) {
                    $data = [
                        'id' => $link->id,
                        'guardian_id' => $link->guardian_id,
                        'guardian_type' => $link->guardian_type,
                        'title_prefix' => $link->title_prefix,
                        'first_name' => $link->first_name,
                        'last_name' => $link->last_name,
                        'relationship' => $link->relationship,
                        'occupation' => $link->occupation,
                        'workplace' => $link->workplace,
                        'nationality' => $link->nationality,
                        'status' => $link->status,
                        'is_primary_contact' => $link->is_primary_contact,
                        'is_emergency_contact' => $link->is_emergency_contact,
                        'link_id' => $link->id,
                        'appointed_by_role' => $link->appointed_by_role,
                        'verified_at' => $link->verified_at,
                        'is_verified' => $link->verified_at !== null,
                        'linked_user_id' => $link->guardian?->user_id,
                        'linked_user_name' => $link->guardian?->user?->name,
                        'has_pending_account_request' => in_array($link->guardian_id, $pendingGuardianIds, true),
                        'contacts' => $link->guardian?->contacts->map(fn ($contact): array => [
                            'id' => $contact->id,
                            'contact_type' => $contact->contact_type,
                            'contact_value' => $contact->contact_value,
                            'is_primary' => $contact->is_primary,
                        ])->values()->all() ?? [],
                    ];

                    if ($showSensitive && ! $access->isBlockedGuardianRow($blockedGuardianIds, $link)) {
                        $data['citizen_id'] = $link->citizen_id;
                        $data['monthly_income'] = $link->monthly_income;
                    }

                    return $data;
                })->values()->all();
            }),
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
