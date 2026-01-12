<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\UserProfile;

class SettingsController extends Controller
{
    /**
     * Get user settings (profile + account info)
     */
    public function index()
    {
        $user = Auth::user();
        $user->load('profile');
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update Profile Info (Bio, Location, Socials, etc.)
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        // Validate
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'birthdate' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'social_media_links' => 'nullable|array',
        ]);

        // Create profile if not exists
        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);
        
        // Update fields
        $profile->fill($validated);
        $user->profile()->save($profile);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->load('profile')
        ]);
    }

    /**
     * Update Account Info (Name, Phone)
     */
    public function updateAccount(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            // Email updates usually require verification, skipping for now or strictly validating unique
            // 'email' => 'required|email|unique:users,email,'.$user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Account info updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Update Password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided password does not match your current password.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }

    /**
     * Update Avatar
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:10240', // 10MB max
        ]);

        $user = Auth::user();

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            
            // Start updating User model (standard Laravel Jetstream attribute is profile_photo_path)
            $user->profile_photo_path = Storage::url($path);
            $user->save();

            // Also update UserProfile if needed (some systems use both)
            if ($user->profile) {
                $user->profile->profile_picture = Storage::url($path);
                $user->profile->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Avatar updated successfully',
                'url' => Storage::url($path)
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
    }

    /**
     * Update Cover Image
     */
    public function updateCover(Request $request)
    {
        $request->validate([
            'cover' => 'required|image|max:10240', // 10MB max
        ]);

        $user = Auth::user();
        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);

        if ($request->hasFile('cover')) {
            $path = $request->file('cover')->store('covers', 'public');
            
            $profile->cover_image = Storage::url($path);
            $profile->save();

            return response()->json([
                'success' => true,
                'message' => 'Cover image updated successfully',
                'url' => Storage::url($path)
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
    }
}
