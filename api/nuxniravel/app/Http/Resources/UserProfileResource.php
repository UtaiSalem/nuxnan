<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        // Generate avatar URL
        $avatarUrl = $this->getAvatarUrl($user);
        
        // Generate cover URL
        $coverUrl = $this->getCoverUrl();

        // Calculate profile completion
        $completion = $this->calculateProfileCompletion($user);

        // Calculate level from points
        $points = $user->pp ?? 0;
        $levelData = $this->calculateLevel($points);

        return [
            'id'                => $this->id,
            'user_id'           => $this->user_id,
            
            // User basic info
            'username'          => $user->name,
            'email'             => $user->email,
            'phone'             => $user->phone_number,
            'personal_code'     => $user->personal_code,
            'reference_code'    => $user->reference_code,
            
            // Profile info
            'first_name'        => $this->first_name,
            'last_name'         => $this->last_name,
            'full_name'         => trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: $user->name,
            'bio'               => $this->bio,
            'birthdate'         => $this->birthdate,
            'gender'            => $this->gender,
            'location'          => $this->location,
            'website'           => $this->website,
            'interests'         => $this->interests,
            
            // Images
            'avatar'            => $avatarUrl,
            'cover_image'       => $coverUrl,
            
            // Social
            'social_media_links'=> $this->social_media_links ? json_decode($this->social_media_links, true) : [],
            
            // Stats
            'followers'         => $this->followers ?? 0,
            'following'         => $this->following ?? 0,
            'friends'           => $this->friends ?? 0,
            'friends_count'     => $this->friends ?? 0,
            'posts_count'       => $user->activities()->count(),
            'visits_count'      => $this->profile_views ?? 0,
            
            // Level & Experience
            'level'             => $levelData['level'],
            'grade'             => $this->getStudentGrade($user), // ระดับชั้น ม.1-6
            'experience'        => $levelData['current_xp'],
            'experience_to_next_level' => $levelData['xp_to_next'],
            
            // Points & Wallet
            'points'            => $user->pp ?? 0,
            'wallet'            => $user->wallet ?? 0,
            
            // Settings
            'privacy_settings'  => $this->privacy_settings ?? 'public',
            
            // Profile Completion
            'profile_completion'=> $completion,
            
            // Dates
            'join_date'         => $this->join_date ?? $user->created_at,
            'last_login'        => $this->last_login,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            
            // Additional user info
            'is_verified'       => $user->hasVerifiedEmail(),
            'is_plearnd_admin'  => $user->isPlearndAdmin(),
        ];
    }

    /**
     * Get avatar URL
     */
    private function getAvatarUrl($user): string
    {
        if ($user->profile_photo_path) {
            if (filter_var($user->profile_photo_path, FILTER_VALIDATE_URL)) {
                return $user->profile_photo_path;
            }
            return url(Storage::url($user->profile_photo_path));
        }
        
        // Check profile picture
        if ($this->profile_picture) {
            if (filter_var($this->profile_picture, FILTER_VALIDATE_URL)) {
                return $this->profile_picture;
            }
            return url(Storage::url($this->profile_picture));
        }
        
        // Fallback to UI Avatars
        $name = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: $user->name;
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=7F9CF5&background=EBF4FF&size=200';
    }

    /**
     * Get cover image URL
     */
    private function getCoverUrl(): ?string
    {
        $coverPath = $this->cover_image ?? $this->cover_image_url;
        
        if ($coverPath) {
            if (filter_var($coverPath, FILTER_VALIDATE_URL)) {
                return $coverPath;
            }
            return url(Storage::url($coverPath));
        }
        
        return null;
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion($user): array
    {
        $fields = [
            'avatar'        => ['weight' => 15, 'completed' => !empty($user->profile_photo_path) || !empty($this->profile_picture)],
            'cover'         => ['weight' => 10, 'completed' => !empty($this->cover_image) || !empty($this->cover_image_url)],
            'first_name'    => ['weight' => 10, 'completed' => !empty($this->first_name)],
            'last_name'     => ['weight' => 10, 'completed' => !empty($this->last_name)],
            'bio'           => ['weight' => 15, 'completed' => !empty($this->bio)],
            'birthdate'     => ['weight' => 5, 'completed' => !empty($this->birthdate)],
            'gender'        => ['weight' => 5, 'completed' => !empty($this->gender)],
            'location'      => ['weight' => 10, 'completed' => !empty($this->location)],
            'website'       => ['weight' => 5, 'completed' => !empty($this->website)],
            'interests'     => ['weight' => 10, 'completed' => !empty($this->interests)],
            'social_media'  => ['weight' => 5, 'completed' => !empty($this->social_media_links)],
        ];

        $totalWeight = 0;
        $completedWeight = 0;
        $missingFields = [];

        foreach ($fields as $field => $data) {
            $totalWeight += $data['weight'];
            if ($data['completed']) {
                $completedWeight += $data['weight'];
            } else {
                $missingFields[] = [
                    'field' => $field,
                    'weight' => $data['weight'],
                    'label' => $this->getFieldLabel($field),
                ];
            }
        }

        $percentage = $totalWeight > 0 ? round(($completedWeight / $totalWeight) * 100) : 0;

        return [
            'percentage'        => $percentage,
            'completed_weight'  => $completedWeight,
            'total_weight'      => $totalWeight,
            'missing_fields'    => $missingFields,
            'is_complete'       => $percentage >= 100,
        ];
    }

    /**
     * Get human readable field label
     */
    private function getFieldLabel(string $field): string
    {
        $labels = [
            'avatar'        => 'อัพโหลดรูปโปรไฟล์',
            'cover'         => 'อัพโหลดรูปปก',
            'first_name'    => 'เพิ่มชื่อจริง',
            'last_name'     => 'เพิ่มนามสกุล',
            'bio'           => 'เพิ่มประวัติย่อ',
            'birthdate'     => 'เพิ่มวันเกิด',
            'gender'        => 'ระบุเพศ',
            'location'      => 'เพิ่มที่อยู่',
            'website'       => 'เพิ่มเว็บไซต์',
            'interests'     => 'เพิ่มความสนใจ',
            'social_media'  => 'เชื่อมต่อโซเชียลมีเดีย',
        ];

        return $labels[$field] ?? $field;
    }

    /**
     * Calculate user level from points
     * Level formula: Points needed = 100 * level^1.5
     * 
     * Level Tiers & Border Colors:
     * 1-4:   Gray (#969696)     - Beginner
     * 5-9:   Green (#4fc35b)    - Novice  
     * 10-19: Teal (#1bc5bd)     - Apprentice
     * 20-29: Cyan (#23d2e2)     - Journeyman
     * 30-39: Blue (#3b82f6)     - Expert
     * 40-49: Purple (#615dfa)   - Master
     * 50-59: Orange (#f59e0b)   - Grandmaster
     * 60-79: Red (#ef4444)      - Legend
     * 80-99: Pink (#ec4899)     - Mythic
     * 100+:  Gold (#fbbf24)     - Immortal
     */
    private function calculateLevel(int $points): array
    {
        // XP thresholds for each level (cumulative)
        // Formula: totalXP = 100 * (level^1.5)
        $level = 1;
        $totalXpForCurrentLevel = 0;
        $totalXpForNextLevel = 100;

        while ($points >= $totalXpForNextLevel && $level < 999) {
            $level++;
            $totalXpForCurrentLevel = $totalXpForNextLevel;
            $totalXpForNextLevel = (int) round(100 * pow($level + 1, 1.5));
        }

        $currentLevelXp = $points - $totalXpForCurrentLevel;
        $xpNeededForNextLevel = $totalXpForNextLevel - $totalXpForCurrentLevel;
        $xpToNext = max(0, $totalXpForNextLevel - $points);
        
        // Progress percentage within current level
        $progress = $xpNeededForNextLevel > 0 
            ? round(($currentLevelXp / $xpNeededForNextLevel) * 100) 
            : 100;

        return [
            'level'         => $level,
            'current_xp'    => $points,
            'level_xp'      => $currentLevelXp,
            'xp_to_next'    => $xpToNext,
            'xp_for_level'  => $xpNeededForNextLevel,
            'progress'      => min(100, $progress),
        ];
    }

    /**
     * Get student grade level (ม.1-6)
     * Returns grade 1-6 based on student info
     * If no student record, defaults to 6 (highest)
     */
    private function getStudentGrade($user): int
    {
        // Try to get grade from student relationship
        if ($user->student) {
            $classLevel = $user->student->class_level ?? null;
            if ($classLevel) {
                // Parse class level (e.g., "ม.1", "ม.2", etc.)
                if (preg_match('/[มm]\.?(\d)/i', $classLevel, $matches)) {
                    return (int) $matches[1];
                }
                // Try numeric only
                if (is_numeric($classLevel) && $classLevel >= 1 && $classLevel <= 6) {
                    return (int) $classLevel;
                }
            }
        }

        // Try to get from student_cards
        if (method_exists($user, 'studentCards') && $user->studentCards) {
            $studentCard = $user->studentCards->first();
            if ($studentCard && $studentCard->class_level) {
                $level = $studentCard->class_level;
                if ($level >= 1 && $level <= 6) {
                    return (int) $level;
                }
            }
        }

        // Default: calculate grade from user level (1-10=ม.1, 11-20=ม.2, etc.)
        $points = $user->pp ?? 0;
        $levelData = $this->calculateLevel($points);
        $userLevel = $levelData['level'];
        
        // Map user level to grade
        if ($userLevel >= 50) return 6;
        if ($userLevel >= 40) return 5;
        if ($userLevel >= 30) return 4;
        if ($userLevel >= 20) return 3;
        if ($userLevel >= 10) return 2;
        return 1;
    }
}
