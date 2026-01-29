<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Generate avatar URL - trust the model accessor
        $avatarUrl = $this->profile_photo_url;
        
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'username'          => $this->name,
            'email'             => $this->email,
            'phone'             => $this->phone_number,
            'avatar'            => $avatarUrl,
            'profile_photo_url' => $this->profile_photo_url,
            'profile_photo_path' => $this->profile_photo_path,
            'points'            => $this->pp,
            'wallet'            => $this->wallet,
            'personal_code'     => $this->personal_code,
            'reference_code'    => $this->reference_code,
            'is_plearnd_admin'  => $this->isPlearndAdmin(),
            'is_super_admin'    => $this->isSuperAdmin(),
            'is_email_verified' => $this->hasVerifiedEmail(),
            'created_at'        => $this->created_at,
            'profile'           => $this->whenLoaded('profile', function () {
                if (!$this->profile) return null;
                return [
                    'first_name'        => $this->profile->first_name,
                    'last_name'         => $this->profile->last_name,
                    'bio'               => $this->profile->bio,
                    'location'          => $this->profile->location,
                    'website'           => $this->profile->website,
                    'social_media_links'=> $this->profile->social_media_links,
                ];
            }),
            'roles'             => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name');
            }),
        ];
    }
}
