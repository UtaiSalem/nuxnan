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
            'xp'                => (int) ($this->xp ?? 0),
            'xp_level'          => (int) ($this->xp_level ?? $this->level ?? 1),
            'level'             => (int) ($this->level ?? $this->xp_level ?? 1),
            'current_xp'        => (int) ($this->current_xp ?? 0),
            'xp_for_next_level' => (int) ($this->xp_for_next_level ?? 0),
            'level_progress'    => $this->levelProgress(),
            'posts_count'       => $this->countValue('posts'),
            'friends_count'     => $this->friendsCount(),
            'followers_count'   => $this->countValue('followers'),
            'following_count'   => $this->countValue('following'),
            'visits_count'      => (int) ($this->visits_count ?? $this->visits ?? 0),
            'posts'             => $this->countValue('posts'),
            'friends'           => $this->friendsCount(),
            'visits'            => (int) ($this->visits_count ?? $this->visits ?? 0),
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

    private function levelProgress(): int
    {
        $currentXp = (int) ($this->current_xp ?? 0);
        $xpForNextLevel = (int) ($this->xp_for_next_level ?? 0);

        if ($xpForNextLevel <= 0) {
            return 100;
        }

        return (int) min(100, max(0, round(($currentXp / $xpForNextLevel) * 100)));
    }

    private function countValue(string $relation): int
    {
        $countAttribute = "{$relation}_count";

        if (isset($this->{$countAttribute})) {
            return (int) $this->{$countAttribute};
        }

        if (!method_exists($this->resource, $relation)) {
            return 0;
        }

        try {
            return (int) $this->{$relation}()->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function friendsCount(): int
    {
        if (isset($this->friends_count)) {
            return (int) $this->friends_count;
        }

        if (!method_exists($this->resource, 'friends')) {
            return 0;
        }

        try {
            return (int) $this->friends()->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
