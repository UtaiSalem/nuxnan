<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminRbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ====================================
        // 1. Create Permissions
        // ====================================
        $permissions = [
            // User Management
            ['name' => 'user-list', 'display_name' => 'ดูรายการผู้ใช้', 'group' => 'users'],
            ['name' => 'user-create', 'display_name' => 'สร้างผู้ใช้', 'group' => 'users'],
            ['name' => 'user-edit', 'display_name' => 'แก้ไขผู้ใช้', 'group' => 'users'],
            ['name' => 'user-delete', 'display_name' => 'ลบผู้ใช้', 'group' => 'users'],
            ['name' => 'user-ban', 'display_name' => 'แบน/ปลดแบนผู้ใช้', 'group' => 'users'],

            // Role Management
            ['name' => 'role-list', 'display_name' => 'ดูรายการ Role', 'group' => 'roles'],
            ['name' => 'role-create', 'display_name' => 'สร้าง Role', 'group' => 'roles'],
            ['name' => 'role-edit', 'display_name' => 'แก้ไข Role', 'group' => 'roles'],
            ['name' => 'role-delete', 'display_name' => 'ลบ Role', 'group' => 'roles'],

            // Permission Management
            ['name' => 'permission-list', 'display_name' => 'ดูรายการ Permission', 'group' => 'permissions'],
            ['name' => 'permission-create', 'display_name' => 'สร้าง Permission', 'group' => 'permissions'],
            ['name' => 'permission-edit', 'display_name' => 'แก้ไข Permission', 'group' => 'permissions'],
            ['name' => 'permission-delete', 'display_name' => 'ลบ Permission', 'group' => 'permissions'],

            // Course Management
            ['name' => 'course-list', 'display_name' => 'ดูรายการคอร์ส', 'group' => 'courses'],
            ['name' => 'course-create', 'display_name' => 'สร้างคอร์ส', 'group' => 'courses'],
            ['name' => 'course-edit', 'display_name' => 'แก้ไขคอร์ส', 'group' => 'courses'],
            ['name' => 'course-delete', 'display_name' => 'ลบคอร์ส', 'group' => 'courses'],
            ['name' => 'course-publish', 'display_name' => 'เผยแพร่คอร์ส', 'group' => 'courses'],

            // Academy Management
            ['name' => 'academy-list', 'display_name' => 'ดูรายการอะคาเดมี', 'group' => 'academies'],
            ['name' => 'academy-create', 'display_name' => 'สร้างอะคาเดมี', 'group' => 'academies'],
            ['name' => 'academy-edit', 'display_name' => 'แก้ไขอะคาเดมี', 'group' => 'academies'],
            ['name' => 'academy-delete', 'display_name' => 'ลบอะคาเดมี', 'group' => 'academies'],

            // Wallet/Points Management
            ['name' => 'wallet-list', 'display_name' => 'ดูรายการ Wallet', 'group' => 'wallet'],
            ['name' => 'wallet-manage', 'display_name' => 'จัดการ Wallet', 'group' => 'wallet'],
            ['name' => 'points-list', 'display_name' => 'ดูรายการ Points', 'group' => 'wallet'],
            ['name' => 'points-manage', 'display_name' => 'จัดการ Points', 'group' => 'wallet'],
            ['name' => 'deposit-approve', 'display_name' => 'อนุมัติคำขอเติมเงิน', 'group' => 'wallet'],

            // Coupon Management
            ['name' => 'coupon-list', 'display_name' => 'ดูรายการคูปอง', 'group' => 'coupons'],
            ['name' => 'coupon-create', 'display_name' => 'สร้างคูปอง', 'group' => 'coupons'],
            ['name' => 'coupon-edit', 'display_name' => 'แก้ไขคูปอง', 'group' => 'coupons'],
            ['name' => 'coupon-delete', 'display_name' => 'ลบคูปอง', 'group' => 'coupons'],

            // Report/Analytics
            ['name' => 'report-view', 'display_name' => 'ดูรายงาน', 'group' => 'reports'],
            ['name' => 'report-export', 'display_name' => 'Export รายงาน', 'group' => 'reports'],

            // System Settings
            ['name' => 'settings-view', 'display_name' => 'ดูการตั้งค่าระบบ', 'group' => 'settings'],
            ['name' => 'settings-edit', 'display_name' => 'แก้ไขการตั้งค่าระบบ', 'group' => 'settings'],
        ];

        foreach ($permissions as $permData) {
            Permission::firstOrCreate(
                ['name' => $permData['name']],
                [
                    'display_name' => $permData['display_name'],
                    'group' => $permData['group'],
                    'description' => $permData['display_name'],
                ]
            );
        }

        $this->command->info('✓ Created ' . count($permissions) . ' permissions');

        // ====================================
        // 2. Create Roles
        // ====================================
        $roles = [
            [
                'name' => 'SUPER_ADMIN',
                'display_name' => 'Super Administrator',
                'description' => 'มีสิทธิ์ทุกอย่างในระบบ',
                'permissions' => [], // Super Admin has all permissions by default in code
            ],
            [
                'name' => 'ADMIN',
                'display_name' => 'Administrator',
                'description' => 'ผู้ดูแลระบบ',
                'permissions' => [
                    'user-list', 'user-create', 'user-edit', 'user-delete', 'user-ban',
                    'role-list',
                    'course-list', 'course-create', 'course-edit', 'course-delete', 'course-publish',
                    'academy-list', 'academy-create', 'academy-edit', 'academy-delete',
                    'wallet-list', 'wallet-manage', 'points-list', 'points-manage', 'deposit-approve',
                    'coupon-list', 'coupon-create', 'coupon-edit', 'coupon-delete',
                    'report-view', 'report-export',
                ],
            ],
            [
                'name' => 'MODERATOR',
                'display_name' => 'Moderator',
                'description' => 'ผู้ดูแลเนื้อหา',
                'permissions' => [
                    'user-list',
                    'course-list', 'course-edit',
                    'academy-list', 'academy-edit',
                    'report-view',
                ],
            ],
            [
                'name' => 'INSTRUCTOR',
                'display_name' => 'Instructor',
                'description' => 'ผู้สอน',
                'permissions' => [
                    'course-list', 'course-create', 'course-edit',
                    'report-view',
                ],
            ],
            [
                'name' => 'VENDOR',
                'display_name' => 'Vendor',
                'description' => 'ผู้ขาย',
                'permissions' => [
                    'course-list', 'course-create', 'course-edit',
                    'coupon-list', 'coupon-create',
                    'report-view',
                ],
            ],
            [
                'name' => 'USER',
                'display_name' => 'User',
                'description' => 'ผู้ใช้ทั่วไป',
                'permissions' => [],
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'],
                    'status' => true,
                ]
            );

            // Assign permissions to role
            if (!empty($roleData['permissions'])) {
                $role->givePermissionTo($roleData['permissions']);
            }
        }

        $this->command->info('✓ Created ' . count($roles) . ' roles');

        // ====================================
        // 3. Create Default Super Admin User
        // ====================================
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@nuxnan.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('SuperAdmin@123'),
                'email_verified_at' => now(),
                'reference_code' => Str::uuid(),
                'personal_code' => User::generateReferralCode(),
            ]
        );

        $superAdmin->assignRole('SUPER_ADMIN');

        $this->command->info('✓ Created Super Admin user: superadmin@nuxnan.com');

        // Create demo admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@nuxnan.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Admin@123'),
                'email_verified_at' => now(),
                'reference_code' => Str::uuid(),
                'personal_code' => User::generateReferralCode(),
            ]
        );

        $admin->assignRole('ADMIN');

        $this->command->info('✓ Created Admin user: admin@nuxnan.com');

        $this->command->newLine();
        $this->command->info('========================================');
        $this->command->info('Admin RBAC Seeder completed successfully!');
        $this->command->info('========================================');
        $this->command->info('Login credentials:');
        $this->command->info('Super Admin: superadmin@nuxnan.com / SuperAdmin@123');
        $this->command->info('Admin: admin@nuxnan.com / Admin@123');
    }
}
