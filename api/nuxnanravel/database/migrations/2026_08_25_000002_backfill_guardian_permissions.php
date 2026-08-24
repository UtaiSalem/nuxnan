<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        'director' => ['guardians.view', 'guardians.manage', 'guardians.sensitive.view', 'guardians.sensitive.manage', 'guardians.appoint'],
        'admin' => ['guardians.view', 'guardians.manage', 'guardians.sensitive.view', 'guardians.sensitive.manage', 'guardians.appoint'],
        'registrar' => ['guardians.view', 'guardians.manage', 'guardians.sensitive.view', 'guardians.sensitive.manage', 'guardians.appoint'],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (self::PERMISSIONS as $name => $permissions) {
                DB::table('academy_roles')->where('name', $name)->orderBy('id')->eachById(
                    function (object $role) use ($permissions): void {
                        $current = json_decode($role->permissions ?? '[]', true);
                        $current = is_array($current) ? $current : [];

                        if (in_array('*', $current, true)) {
                            return;
                        }

                        DB::table('academy_roles')->where('id', $role->id)->update([
                            'permissions' => json_encode(array_values(array_unique([
                                ...$current,
                                ...$permissions,
                            ])), JSON_THROW_ON_ERROR),
                        ]);
                    }
                );
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (array_keys(self::PERMISSIONS) as $name) {
                DB::table('academy_roles')->where('name', $name)->orderBy('id')->eachById(
                    function (object $role): void {
                        $permissions = json_decode($role->permissions ?? '[]', true);
                        $permissions = is_array($permissions) ? $permissions : [];

                        if (in_array('*', $permissions, true)) {
                            return;
                        }

                        DB::table('academy_roles')->where('id', $role->id)->update([
                            'permissions' => json_encode(array_values(array_filter(
                                $permissions,
                                fn (mixed $permission): bool => ! str_starts_with((string) $permission, 'guardians.')
                            )), JSON_THROW_ON_ERROR),
                        ]);
                    }
                );
            }
        });
    }
};
