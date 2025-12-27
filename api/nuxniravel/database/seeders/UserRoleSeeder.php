<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $studentRole = Role::where('name', 'STUDENT')->first();

        if (!$studentRole) {
            $this->command->error('STUDENT role not found. Please run RoleSeeder first.');
            return;
        }

        $users = User::all();

        foreach ($users as $user) {
            // Assign STUDENT role if not already assigned
            if (!$user->roles()->where('role_id', $studentRole->id)->exists()) {
                $user->roles()->attach($studentRole->id);
            }
        }

        $this->command->info('All users have been assigned the STUDENT role.');
    }
}
