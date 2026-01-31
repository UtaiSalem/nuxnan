<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'personal_code' => $this->personal_code,
            'reference_code' => $this->reference_code,
            'avatar' => $this->avatar,
            'profile_photo_url' => $this->profile_photo_url,
            
            // Status
            'email_verified_at' => $this->email_verified_at,
            'phone_verified_at' => $this->phone_verified_at,
            'verified' => $this->verified,
            'is_verified' => $this->email_verified_at !== null,
            
            // Points & Wallet
            'pp' => $this->pp,
            'wallet' => $this->wallet,
            'total_points_earned' => $this->total_points_earned,
            'total_points_spent' => $this->total_points_spent,
            'level' => $this->level,
            'current_xp' => $this->current_xp,
            'xp_for_next_level' => $this->xp_for_next_level,
            
            // Roles & Permissions
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name,
                    ];
                });
            }),
            'is_super_admin' => $this->isSuperAdmin(),
            'is_admin' => $this->hasAnyRole(['SUPER_ADMIN', 'ADMIN']),
            
            // Profile
            'profile' => $this->whenLoaded('profile', function () {
                return [
                    'bio' => $this->profile->bio ?? null,
                    'birthday' => $this->profile->birthday ?? null,
                    'gender' => $this->profile->gender ?? null,
                    'address' => $this->profile->address ?? null,
                    'city' => $this->profile->city ?? null,
                    'country' => $this->profile->country ?? null,
                    'website' => $this->profile->website ?? null,
                    'facebook' => $this->profile->facebook ?? null,
                    'twitter' => $this->profile->twitter ?? null,
                    'instagram' => $this->profile->instagram ?? null,
                    'linkedin' => $this->profile->linkedin ?? null,
                ];
            }),
            
            // Social Logins
            'connected_providers' => $this->connected_providers,
            
            // Referral
            'no_of_ref' => $this->no_of_ref,
            'referal_link' => $this->referal_link,
            
            // Timestamps (handle both Carbon objects and strings)
            'created_at' => $this->formatTimestamp($this->created_at),
            'updated_at' => $this->formatTimestamp($this->updated_at),
        ];
    }

    /**
     * Format timestamp handling both Carbon objects and strings
     */
    protected function formatTimestamp($value): ?string
    {
        if ($value === null) {
            return null;
        }
        
        if (is_string($value)) {
            return $value;
        }
        
        return $value->format('Y-m-d H:i:s');
    }
}
