<?php

namespace Database\Seeders;

use App\Models\AcademyRole;
use App\Models\AcademyPermission;
use Illuminate\Database\Seeder;

class AcademyRoleSeeder extends Seeder
{
    /**
     * Seed the default academy roles and permissions.
     */
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedSystemRoles();
    }

    /**
     * Seed all academy permissions
     */
    private function seedPermissions(): void
    {
        $permissions = AcademyPermission::getAllPermissions();

        foreach ($permissions as $permission) {
            AcademyPermission::firstOrCreate(
                ['name' => $permission['name']],
                [
                    'display_name' => $permission['display_name'],
                    'group' => $permission['group'],
                    'description' => $permission['description'] ?? null,
                ]
            );
        }

        $this->command->info('Academy permissions seeded: ' . count($permissions) . ' permissions');
    }

    /**
     * Seed system default roles
     */
    private function seedSystemRoles(): void
    {
        $systemRoles = AcademyRole::SYSTEM_ROLES;

        foreach ($systemRoles as $name => $data) {
            AcademyRole::firstOrCreate(
                [
                    'academy_id' => null, // System role
                    'name' => $name,
                ],
                [
                    'display_name_th' => $data['display_name_th'],
                    'display_name_en' => $data['display_name_en'],
                    'permissions' => $data['permissions'],
                    'color' => $data['color'],
                    'icon' => $data['icon'],
                    'sort_order' => $data['sort_order'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Academy system roles seeded: ' . count($systemRoles) . ' roles');
    }
}
