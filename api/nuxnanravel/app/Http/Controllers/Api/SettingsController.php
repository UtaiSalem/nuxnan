<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserProfile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

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
            // Personal Information
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            // Professional Information
            'job_title' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'skills' => 'nullable|array',
            'experience_years' => 'nullable|string|max:50',
            // Privacy Settings
            'privacy_settings' => 'nullable|in:public,friends,private',
            'show_email' => 'nullable|boolean',
            'show_phone' => 'nullable|boolean',
            'show_birthdate' => 'nullable|boolean',
            'show_location' => 'nullable|boolean',
            'allow_friend_requests' => 'nullable|boolean',
            'allow_messages' => 'nullable|boolean',
            'show_online_status' => 'nullable|boolean',
        ]);

        // Create profile if not exists
        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);
        
        // Update fields
        $profile->fill($validated);
        $user->profile()->save($profile);

        // Update phone_number in users table if provided
        if (isset($validated['phone_number'])) {
            $user->phone_number = $validated['phone_number'];
            $user->save();
        }

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
        try {
            $request->validate([
                'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB max
            ]);

            $file = $request->file('avatar');

            // Additional security check: verify MIME type from file content
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file->getPathname());
            finfo_close($finfo);

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedMimeTypes)) {
                Log::warning('Invalid MIME type for avatar upload', ['user_id' => Auth::id(), 'mime' => $mimeType]);
                return response()->json([
                    'success' => false,
                    'message' => 'ประเภทไฟล์ไม่ถูกต้อง อนุญาตเฉพาะไฟล์รูปภาพเท่านั้น',
                ], 422);
            }

            // Check if file is actually an image
            $imageInfo = getimagesize($file->getPathname());
            if (!$imageInfo) {
                Log::warning('Uploaded file is not a valid image', ['user_id' => Auth::id()]);
                return response()->json([
                    'success' => false,
                    'message' => 'ไฟล์ที่อัปโหลดไม่ใช่รูปภาพที่ถูกต้อง',
                ], 422);
            }

            $user = Auth::user();

            // Delete old avatar if exists
            if ($user->profile_photo_path && !filter_var($user->profile_photo_path, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Resize image to 300x300 max, maintain aspect ratio
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);
            $image->scaleDown(width: 300, height: 300);

            // Generate filename
            $filename = time() . '_' . uniqid() . '.jpg';

            // Store path (consistent with new standard)
            // Stored in public/avatars/{user_id}/{filename}
            $path = 'avatars/' . $user->id . '/' . $filename;

            // Save resized image to storage
            Storage::disk('public')->put($path, (string) $image->toJpeg(quality: 85));

            // Update user with relative path (e.g. avatars/1/file.jpg)
            $user->update([
                'profile_photo_path' => $path,
            ]);

            // Also update UserProfile if exists
            if ($user->profile) {
                $user->profile->update([
                    'profile_picture' => $path,
                ]);
            }

            // Get clean full URL from model accessor
            // We need to refresh/reload or just use the accessor logic manually here if needed, 
            // but simply calling the accessor on the instance should work if attributes were updated.
            $fullUrl = $user->profile_photo_url;

            Log::info('Avatar updated successfully', ['user_id' => $user->id, 'path' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'อัปโหลดรูปโปรไฟล์สำเร็จ',
                'url' => $fullUrl,
                'avatar' => $fullUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Avatar upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอัปโหลดรูปโปรไฟล์ได้ กรุณาลองใหม่อีกครั้ง',
            ], 500);
        }
    }

    /**
     * Update Cover Image
     */
    public function updateCover(Request $request)
    {
        try {
            $request->validate([
                'cover' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'], // 10MB max
            ]);

            $file = $request->file('cover');

            // Additional security check: verify MIME type from file content
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file->getPathname());
            finfo_close($finfo);

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedMimeTypes)) {
                Log::warning('Invalid MIME type for cover upload', ['user_id' => Auth::id(), 'mime' => $mimeType]);
                return response()->json([
                    'success' => false,
                    'message' => 'ประเภทไฟล์ไม่ถูกต้อง อนุญาตเฉพาะไฟล์รูปภาพเท่านั้น',
                ], 422);
            }

            // Check if file is actually an image
            $imageInfo = getimagesize($file->getPathname());
            if (!$imageInfo) {
                Log::warning('Uploaded file is not a valid image', ['user_id' => Auth::id()]);
                return response()->json([
                    'success' => false,
                    'message' => 'ไฟล์ที่อัปโหลดไม่ใช่รูปภาพที่ถูกต้อง',
                ], 422);
            }

            $user = Auth::user();
            $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);

            // Delete old cover if exists
            $oldCover = $profile->cover_image;
            if ($oldCover && !filter_var($oldCover, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($oldCover);
            }

            // Resize image to 1200x400 max, maintain aspect ratio
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);
            $image->scaleDown(width: 1200, height: 400);

            // Generate filename
            $filename = time() . '_' . uniqid() . '.jpg';

            // Store path
            $path = 'covers/' . $user->id . '/' . $filename;

            // Save resized image to storage
            Storage::disk('public')->put($path, (string) $image->toJpeg(quality: 85));

            // Update profile with raw path (no /storage/ prefix)
            $profile->cover_image = $path;
            $profile->save();

            $fullUrl = url(Storage::url($path));

            Log::info('Cover updated successfully', ['user_id' => $user->id, 'path' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'อัปโหลดภาพปกสำเร็จ',
                'url' => $fullUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Cover upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอัปโหลดภาพปกได้ กรุณาลองใหม่อีกครั้ง',
            ], 500);
        }
    }
}
