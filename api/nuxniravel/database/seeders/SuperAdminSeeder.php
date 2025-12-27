<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create the SUPER_ADMIN role
        $superAdminRole = Role::where('name', 'SUPER_ADMIN')->first();

        if (!$superAdminRole) {
            $this->command->error('SUPER_ADMIN role not found. Please run RoleSeeder first.');
            return;
        }

        // Find the first user or create a default super admin
        $user = User::first();

        if (!$user) {
            $this->command->info('No users found. Creating default super admin user...');
            $user = User::create([
                'username' => 'superadmin',
                'email' => 'superadmin@nuxni.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Assign SUPER_ADMIN role
        if (!$user->isSuperAdmin()) {
            $user->assignRole('SUPER_ADMIN');
            $this->command->info("User '{$user->username}' has been assigned SUPER_ADMIN role.");
        } else {
            $this->command->info("User '{$user->username}' is already a SUPER_ADMIN.");
        }
    }
}
