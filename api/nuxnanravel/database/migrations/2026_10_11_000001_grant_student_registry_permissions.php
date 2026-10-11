<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * G4 (เมนู #15 ทะเบียนนักเรียน) — ผูกสิทธิ์ students.* ให้บทบาทที่ดูแลงานทะเบียน
 *
 * คีย์ students.* มีในแคตาล็อก (AcademyPermission::PERMISSIONS['students']) อยู่แล้ว
 * แต่ reconcile migration เดิมไม่ได้ assign ให้ role ไหน ⇒ มอบสิทธิ์ "ฝ่ายทะเบียน" ไม่ได้
 * (มีแต่ owner/admin ที่ bypass) · migration นี้เติมให้ registrar/director/admin
 *
 * แตะเฉพาะ role template ของระบบ (academy_id IS NULL) เหมือน reconcile migration
 * — role instance ของแต่ละโรงเรียนที่ก๊อปไปแล้วไม่ถูกแก้ (เจ้าของปรับผ่านหน้า roles ได้)
 */
return new class extends Migration
{
    private const ADDED = [
        'registrar' => [
            'students.view', 'students.manage', 'students.create', 'students.import',
            'students.lifecycle', 'students.activate_account', 'students.export', 'students.delete',
        ],
        'director' => [
            'students.view', 'students.manage', 'students.create', 'students.import',
            'students.lifecycle', 'students.activate_account', 'students.export', 'students.delete',
        ],
        'admin' => [
            'students.view', 'students.manage', 'students.create', 'students.import',
            'students.lifecycle', 'students.activate_account', 'students.export', 'students.delete',
        ],
    ];

    public function up(): void
    {
        if (DB::table('academy_roles')->whereNull('academy_id')->count() === 0) {
            return;
        }

        DB::transaction(function (): void {
            foreach (self::ADDED as $name => $added) {
                DB::table('academy_roles')
                    ->whereNull('academy_id')
                    ->where('name', $name)
                    ->orderBy('id')
                    ->eachById(function (object $role) use ($added): void {
                        $permissions = json_decode($role->permissions ?? '[]', true);
                        $permissions = is_array($permissions) ? $permissions : [];

                        if (in_array('*', $permissions, true)) {
                            return;
                        }

                        DB::table('academy_roles')->where('id', $role->id)->update([
                            'permissions' => json_encode(array_values(array_unique([
                                ...$permissions,
                                ...$added,
                            ])), JSON_THROW_ON_ERROR),
                            'updated_at' => now(),
                        ]);
                    });
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (self::ADDED as $name => $added) {
                DB::table('academy_roles')
                    ->whereNull('academy_id')
                    ->where('name', $name)
                    ->orderBy('id')
                    ->eachById(function (object $role) use ($added): void {
                        $permissions = json_decode($role->permissions ?? '[]', true);
                        $permissions = is_array($permissions) ? $permissions : [];

                        if (in_array('*', $permissions, true)) {
                            return;
                        }

                        DB::table('academy_roles')->where('id', $role->id)->update([
                            'permissions' => json_encode(array_values(array_filter(
                                $permissions,
                                fn (mixed $permission): bool => ! in_array((string) $permission, $added, true)
                            )), JSON_THROW_ON_ERROR),
                            'updated_at' => now(),
                        ]);
                    });
            }
        });
    }
};
