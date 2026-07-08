<?php

namespace App\Services\Import;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeacherAutoIntakeService
{
    public function autoCreateTeacher(Academy $academy, string $rawName, User $operator): User
    {
        $name = trim(preg_replace('/\s+/u', ' ', $rawName) ?? '');
        if ($name === '') {
            throw new \InvalidArgumentException('Teacher name is required.');
        }

        return DB::transaction(function () use ($academy, $name, $operator): User {
            $role = AcademyRole::query()
                ->where(fn ($query) => $query->whereNull('academy_id')->orWhere('academy_id', $academy->id))
                ->where('name', 'teacher')
                ->orderByRaw('academy_id IS NULL')
                ->firstOrFail();

            $email = 'teacher.'.Str::lower(Str::random(16)).'@placeholder.local';
            $uniqueName = $name;
            for ($suffix = 2; User::withTrashed()->where('name', $uniqueName)->exists(); $suffix++) {
                $uniqueName = "{$name} ({$suffix})";
            }

            $user = User::create([
                'name' => $uniqueName,
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => null,
                'is_placeholder' => true,
                'must_reset_password' => true,
            ]);

            AcademyMember::updateOrCreate(
                ['academy_id' => $academy->id, 'user_id' => $user->id],
                ['role' => 'teacher', 'academy_role_id' => $role->id, 'status' => 1,
                    'invited_by' => $operator->id, 'invited_at' => now()]
            );

            return $user;
        });
    }
}
