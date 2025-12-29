<?php

namespace App\Http\Controllers\Api\Shared;

use App\Models\Post;
use App\Models\User;

use App\Models\Friend;
use App\Models\Activity;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\Play\ActivityResource;
use App\Http\Resources\UserProfileResource;
use App\Http\Requests\StoreUserProfileRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use Carbon\Carbon;

class UserProfileController extends \App\Http\Controllers\Controller
{
    /**
     * Safely parse a date value, returning now() if parsing fails
     */
    private function safeParseDate($date): string
    {
        if (!$date) {
            return now()->format('Y-m-d H:i:s');
        }
        
        try {
            if ($date instanceof Carbon) {
                return $date->format('Y-m-d H:i:s');
            }
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // If date parsing fails, return current time
            return now()->format('Y-m-d H:i:s');
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(User $user)
    {
        // ตรวจสอบว่าผู้ใช้ที่เข้าสู่ระบบเป็นเพื่อนกับผู้ใช้ที่เป็นเจ้าของโปรไฟล์หรือไม่
        $heIsMyFriend = Friend::where('user_id', auth()->id())->where('friend_id', $user->id)
            ->whereStatus(1)->exists();
        
        // ตรวจสอบว่าผู้ใช้ที่เป็นเจ้าของโปรไฟล์ป็นเพื่อนกับผู้ใช้ที่เข้าสู่ระบบเหรือไม่
        $iAmHisFriend = Friend::where('friend_id', auth()->id())->where('user_id', $user->id)
            ->whereStatus(1)->exists();

        $friendWithAuth = $heIsMyFriend || $iAmHisFriend;

        $privacySettings = $friendWithAuth ? [2, 3] : [3];
    

        // ใช้ Eloquent ORM เพื่อดึงโพสต์จากตาราง "กิจกรรม" โดยกำหนดเงื่อนไขตามที่ระบุ
        $activities = Activity::whereHasMorph('activityable', // ชื่อของ relation ในโมเดล Activity
                [Post::class], // ระบุโมเดลที่เป็นไปได้ใน relation
                function ($query) use ($user, $privacySettings) {
                    $query->whereIn('privacy_settings', $privacySettings)
                        ->where(function ($query) use ($user) {
                            $query->where('user_id', $user->id); // โพสต์จากผู้ใช้ที่เป็นเจ้าของโปรไฟล์
                        });
                }
            )
            ->latest()
            ->paginate();

        return response()->json([
            'user'          => $user,
            'activities'    => ActivityResource::collection($activities),
        ]);
    }

    function checkUsernameExists($name) {
        $user = User::where('name', $name)->first();
        if ($user) {
            return response()->json([
                'exists' => true,
                'message' => 'name already exists'
            ]);
        } else {
            return response()->json([
                'exists' => false,
                'message' => 'name is available'
            ]);
        }
    }

    function checkEmailExists($email) {
        $user = User::where('email', $email)->first();
        if ($user) {
            return response()->json([
                'exists' => true,
            ]);
        } else {
            return response()->json([
                'exists' => false,
            ]);
        }
    }

    /**
     * Get current user's profile (authenticated user)
     */
    public function me()
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            // Create profile if doesn't exist
            $profile = $user->profile()->create([
                'join_date' => $this->safeParseDate($user->created_at),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new UserProfileResource($profile),
        ]);
    }

    /**
     * Get user profile by identifier (supports ID, reference_code, or username)
     */
    public function show(string $identifier)
    {
        $authUser = Auth::user();
        
        // Find user by ID, reference_code, or username
        $user = User::where('id', $identifier)
            ->orWhere('reference_code', $identifier)
            ->orWhere('name', $identifier)
            ->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
        
        $profile = $user->profile;

        if (!$profile) {
            $profile = $user->profile()->create([
                'join_date' => $this->safeParseDate($user->created_at),
            ]);
        }

        // Check friendship status
        $friendshipStatus = $this->getFriendshipStatus($authUser, $user);
        
        // Check privacy settings
        $canViewFullProfile = $this->canViewFullProfile($authUser, $user, $profile);

        $profileData = new UserProfileResource($profile);
        
        return response()->json([
            'success' => true,
            'data' => $profileData,
            'friendship_status' => $friendshipStatus,
            'can_view_full_profile' => $canViewFullProfile,
            'is_own_profile' => $authUser->id === $user->id,
        ]);
    }

    /**
     * Update user profile
     */
    public function update(UpdateUserProfileRequest $request)
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            $profile = $user->profile()->create([
                'join_date' => $this->safeParseDate($user->created_at),
            ]);
        }

        $validated = $request->validated();

        // Handle social_media_links as JSON
        if (isset($validated['social_media_links'])) {
            $validated['social_media_links'] = json_encode($validated['social_media_links']);
        }

        $profile->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทโปรไฟล์สำเร็จ',
            'data' => new UserProfileResource($profile->fresh()),
        ]);
    }

    /**
     * Update profile avatar
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB max
        ]);

        $user = Auth::user();

        // Delete old avatar if exists
        if ($user->profile_photo_path && !filter_var($user->profile_photo_path, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars/' . $user->id, 'public');
        
        $user->update([
            'profile_photo_path' => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทรูปโปรไฟล์สำเร็จ',
            'avatar' => url(Storage::url($path)),
        ]);
    }

    /**
     * Update cover image
     */
    public function updateCover(Request $request)
    {
        $request->validate([
            'cover' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'], // 10MB max
        ]);

        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            $profile = $user->profile()->create([
                'join_date' => $user->created_at,
            ]);
        }

        // Delete old cover if exists
        $oldCover = $profile->cover_image ?? $profile->cover_image_url;
        if ($oldCover && !filter_var($oldCover, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($oldCover);
        }

        // Store new cover
        $path = $request->file('cover')->store('covers/' . $user->id, 'public');
        
        $profile->update([
            'cover_image' => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทรูปปกสำเร็จ',
            'cover_image' => url(Storage::url($path)),
        ]);
    }

    /**
     * Get profile completion status
     */
    public function completion()
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            $profile = $user->profile()->create([
                'join_date' => $user->created_at,
            ]);
        }

        $profileResource = new UserProfileResource($profile);
        $data = $profileResource->toArray(request());

        return response()->json([
            'success' => true,
            'data' => $data['profile_completion'],
        ]);
    }

    /**
     * Update privacy settings
     */
    public function updatePrivacy(Request $request)
    {
        $request->validate([
            'privacy_settings' => ['required', 'string', 'in:public,friends,private'],
        ]);

        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            $profile = $user->profile()->create([
                'join_date' => $user->created_at,
            ]);
        }

        $profile->update([
            'privacy_settings' => $request->privacy_settings,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทการตั้งค่าความเป็นส่วนตัวสำเร็จ',
            'privacy_settings' => $profile->privacy_settings,
        ]);
    }

    /**
     * Get user activities by identifier (supports ID, reference_code, or username)
     */
    public function activities(string $identifier)
    {
        $authUser = Auth::user();
        
        // Find user by ID, reference_code, or username
        $user = User::where('id', $identifier)
            ->orWhere('reference_code', $identifier)
            ->orWhere('name', $identifier)
            ->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // ตรวจสอบว่าผู้ใช้ที่เข้าสู่ระบบเป็นเพื่อนกับผู้ใช้ที่เป็นเจ้าของโปรไฟล์หรือไม่
        $heIsMyFriend = Friend::where('user_id', $authUser->id)->where('friend_id', $user->id)
            ->whereStatus(1)->exists();
        
        // ตรวจสอบว่าผู้ใช้ที่เป็นเจ้าของโปรไฟล์ป็นเพื่อนกับผู้ใช้ที่เข้าสู่ระบบเหรือไม่
        $iAmHisFriend = Friend::where('friend_id', $authUser->id)->where('user_id', $user->id)
            ->whereStatus(1)->exists();

        $friendWithAuth = $heIsMyFriend || $iAmHisFriend;
        
        // ถ้าเป็นโปรไฟล์ของตัวเอง ให้ดูได้ทุกอย่าง
        $isOwnProfile = $authUser->id === $user->id;

        $privacySettings = $isOwnProfile ? [1, 2, 3] : ($friendWithAuth ? [2, 3] : [3]);

        // ใช้ Eloquent ORM เพื่อดึงโพสต์จากตาราง "กิจกรรม" โดยกำหนดเงื่อนไขตามที่ระบุ
        $activities = Activity::with([
                'user',
                'activityable',
                'activityable.user',
                'activityable.postImages',
                'activityable.postComments',
            ])
            ->whereHasMorph(
                'activityable',
                [Post::class],
                function ($query) use ($user, $privacySettings) {
                    $query->whereIn('privacy_settings', $privacySettings)
                        ->where('user_id', $user->id);
                }
            )
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'user' => $user,
            'activities' => ActivityResource::collection($activities),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Get user stats by identifier (supports ID, reference_code, or username)
     */
    public function stats(string $identifier = null)
    {
        // If no identifier, use auth user
        if (!$identifier) {
            $user = Auth::user();
        } else {
            // Find user by ID, reference_code, or username
            $user = User::where('id', $identifier)
                ->orWhere('reference_code', $identifier)
                ->orWhere('name', $identifier)
                ->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }
        }
        
        $postsCount = Activity::where('user_id', $user->id)->count();
        
        // Get friends count
        $friendsCount = Friend::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('friend_id', $user->id);
        })->where('status', 1)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'posts' => $postsCount,
                'followers' => $user->profile->followers ?? 0,
                'following' => $user->profile->following ?? 0,
                'friends' => $friendsCount,
                'points' => $user->pp ?? 0,
                'wallet' => $user->wallet ?? 0,
            ],
        ]);
    }

    /**
     * Get friendship status between auth user and target user
     */
    private function getFriendshipStatus($authUser, $targetUser): array
    {
        if ($authUser->id === $targetUser->id) {
            return ['status' => 'self', 'label' => 'ตัวเอง'];
        }

        // Check if they are friends
        $friendship = Friend::where(function ($query) use ($authUser, $targetUser) {
            $query->where('user_id', $authUser->id)->where('friend_id', $targetUser->id);
        })->orWhere(function ($query) use ($authUser, $targetUser) {
            $query->where('user_id', $targetUser->id)->where('friend_id', $authUser->id);
        })->first();

        if (!$friendship) {
            return ['status' => 'none', 'label' => 'ไม่ใช่เพื่อน'];
        }

        if ($friendship->status === 1) {
            return ['status' => 'friends', 'label' => 'เพื่อน'];
        }

        if ($friendship->user_id === $authUser->id) {
            return ['status' => 'pending_sent', 'label' => 'รอการยืนยัน'];
        }

        return ['status' => 'pending_received', 'label' => 'รอการตอบรับ'];
    }

    /**
     * Check if user can view full profile
     */
    private function canViewFullProfile($authUser, $targetUser, $profile): bool
    {
        if ($authUser->id === $targetUser->id) {
            return true;
        }

        $privacySetting = $profile->privacy_settings ?? 'public';

        if ($privacySetting === 'public') {
            return true;
        }

        if ($privacySetting === 'private') {
            return false;
        }

        // For 'friends' privacy, check friendship
        $areFriends = Friend::where(function ($query) use ($authUser, $targetUser) {
            $query->where('user_id', $authUser->id)->where('friend_id', $targetUser->id);
        })->orWhere(function ($query) use ($authUser, $targetUser) {
            $query->where('user_id', $targetUser->id)->where('friend_id', $authUser->id);
        })->where('status', 1)->exists();

        return $areFriends;
    }

}
