<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'SUPER_ADMIN', 'description' => 'Super Administrator with highest privileges'],
            ['name' => 'ADMIN', 'description' => 'Administrator with full access'],
            ['name' => 'INSTRUCTOR', 'description' => 'Course instructor'],
            ['name' => 'VENDOR', 'description' => 'Product vendor'],
            ['name' => 'STUDENT', 'description' => 'Standard user/student'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
