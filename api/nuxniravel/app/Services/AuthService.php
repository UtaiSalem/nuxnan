<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

class AuthService
{
    /**
     * Register a new user with profile and default role.
     *
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function register(array $data): User
    {
        try {
            DB::beginTransaction();

            // Create user
            $user = User::create([
                'email' => $data['email'],
                'username' => $data['username'],
                'referral_code' => User::generateReferralCode(),
                'referrer_code' => $data['referral_code'] ?? null,
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'avatar' => $data['profile_image_url'] ?? null,
                'verified' => false,
            ]);

            // Create user profile
            $this->createUserProfile($user, $data);

            // Assign default role (STUDENT)
            $this->assignDefaultRole($user);

            DB::commit();

            // Load relationships
            $user->load(['profile', 'roles']);

            // Send welcome email
            try {
                Mail::to($user->email)->send(new WelcomeEmail($user));
            } catch (\Exception $e) {
                // Log error but don't fail registration
                \Illuminate\Support\Facades\Log::error('Failed to send welcome email: ' . $e->getMessage());
            }

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create user profile.
     *
     * @param User $user
     * @param array $data
     * @return UserProfile
     */
    protected function createUserProfile(User $user, array $data): UserProfile
    {
        return $user->profile()->create([
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'bio' => $data['bio'] ?? null,
        ]);
    }

    /**
     * Assign default role to user.
     *
     * @param User $user
     * @return void
     */
    public function assignDefaultRole(User $user): void
    {
        $studentRole = Role::where('name', 'STUDENT')->first();

        if ($studentRole) {
            $user->roles()->attach($studentRole->id);
        }
    }

    /**
     * Generate token response with user data.
     *
     * @param string $token
     * @param User $user
     * @return array
     */
    public function generateTokenResponse(string $token, User $user): array
    {
        // Load relationships if not already loaded
        if (!$user->relationLoaded('profile')) {
            $user->load('profile');
        }
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        return [
            'user' => new \App\Http\Resources\UserResource($user),
            'accessToken' => $token,
            'tokenType' => 'bearer',
            'expiresIn' => auth('api')->factory()->getTTL() * 60, // Convert to seconds
        ];
    }

    /**
     * Get authenticated user with relationships.
     *
     * @return User
     */
    public function getAuthenticatedUser(): User
    {
        $user = auth('api')->user();
        $user->load(['profile', 'roles']);
        return $user;
    }
}
