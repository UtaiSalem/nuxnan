<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // Users Management
            ['name' => 'user-list', 'display_name' => 'ดูรายการผู้ใช้', 'group' => 'users'],
            ['name' => 'user-create', 'display_name' => 'สร้างผู้ใช้', 'group' => 'users'],
            ['name' => 'user-edit', 'display_name' => 'แก้ไขผู้ใช้', 'group' => 'users'],
            ['name' => 'user-delete', 'display_name' => 'ลบผู้ใช้', 'group' => 'users'],
            ['name' => 'user-verify', 'display_name' => 'ยืนยันอีเมลผู้ใช้', 'group' => 'users'],

            // Roles Management
            ['name' => 'role-list', 'display_name' => 'ดูรายการ Role', 'group' => 'roles'],
            ['name' => 'role-create', 'display_name' => 'สร้าง Role', 'group' => 'roles'],
            ['name' => 'role-edit', 'display_name' => 'แก้ไข Role', 'group' => 'roles'],
            ['name' => 'role-delete', 'display_name' => 'ลบ Role', 'group' => 'roles'],

            // Permissions Management
            ['name' => 'permission-list', 'display_name' => 'ดูรายการ Permission', 'group' => 'permissions'],
            ['name' => 'permission-create', 'display_name' => 'สร้าง Permission', 'group' => 'permissions'],
            ['name' => 'permission-edit', 'display_name' => 'แก้ไข Permission', 'group' => 'permissions'],
            ['name' => 'permission-delete', 'display_name' => 'ลบ Permission', 'group' => 'permissions'],

            // Courses Management
            ['name' => 'course-list', 'display_name' => 'ดูรายการคอร์ส', 'group' => 'courses'],
            ['name' => 'course-create', 'display_name' => 'สร้างคอร์ส', 'group' => 'courses'],
            ['name' => 'course-edit', 'display_name' => 'แก้ไขคอร์ส', 'group' => 'courses'],
            ['name' => 'course-delete', 'display_name' => 'ลบคอร์ส', 'group' => 'courses'],
            ['name' => 'course-approve', 'display_name' => 'อนุมัติคอร์ส', 'group' => 'courses'],

            // Academies Management
            ['name' => 'academy-list', 'display_name' => 'ดูรายการอะคาเดมี', 'group' => 'academies'],
            ['name' => 'academy-create', 'display_name' => 'สร้างอะคาเดมี', 'group' => 'academies'],
            ['name' => 'academy-edit', 'display_name' => 'แก้ไขอะคาเดมี', 'group' => 'academies'],
            ['name' => 'academy-delete', 'display_name' => 'ลบอะคาเดมี', 'group' => 'academies'],

            // Content Moderation
            ['name' => 'post-moderate', 'display_name' => 'ตรวจสอบโพสต์', 'group' => 'moderation'],
            ['name' => 'comment-moderate', 'display_name' => 'ตรวจสอบคอมเมนต์', 'group' => 'moderation'],
            ['name' => 'report-view', 'display_name' => 'ดูรายงาน', 'group' => 'moderation'],

            // Wallet & Transactions
            ['name' => 'wallet-view', 'display_name' => 'ดูกระเป๋าเงิน', 'group' => 'finance'],
            ['name' => 'wallet-manage', 'display_name' => 'จัดการกระเป๋าเงิน', 'group' => 'finance'],
            ['name' => 'transaction-view', 'display_name' => 'ดูรายการธุรกรรม', 'group' => 'finance'],
            ['name' => 'deposit-approve', 'display_name' => 'อนุมัติการฝากเงิน', 'group' => 'finance'],

            // System Settings
            ['name' => 'setting-view', 'display_name' => 'ดูการตั้งค่า', 'group' => 'settings'],
            ['name' => 'setting-edit', 'display_name' => 'แก้ไขการตั้งค่า', 'group' => 'settings'],

            // Dashboard & Reports
            ['name' => 'dashboard-view', 'display_name' => 'ดู Dashboard', 'group' => 'dashboard'],
            ['name' => 'analytics-view', 'display_name' => 'ดูสถิติ', 'group' => 'dashboard'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('✅ Permissions created successfully!');

        // Create Roles
        $superAdmin = Role::firstOrCreate(
            ['name' => 'SUPER_ADMIN'],
            [
                'display_name' => 'Super Administrator',
                'description' => 'มีสิทธิ์เข้าถึงทุกอย่างในระบบ',
                'status' => true,
            ]
        );

        $admin = Role::firstOrCreate(
            ['name' => 'ADMIN'],
            [
                'display_name' => 'Administrator',
                'description' => 'ผู้ดูแลระบบทั่วไป',
                'status' => true,
            ]
        );

        $moderator = Role::firstOrCreate(
            ['name' => 'MODERATOR'],
            [
                'display_name' => 'Moderator',
                'description' => 'ผู้ดูแลเนื้อหาและตรวจสอบรายงาน',
                'status' => true,
            ]
        );

        $instructor = Role::firstOrCreate(
            ['name' => 'INSTRUCTOR'],
            [
                'display_name' => 'Instructor',
                'description' => 'ครูผู้สอน สร้างและจัดการคอร์สของตัวเอง',
                'status' => true,
            ]
        );

        $user = Role::firstOrCreate(
            ['name' => 'USER'],
            [
                'display_name' => 'User',
                'description' => 'ผู้ใช้ทั่วไป',
                'status' => true,
            ]
        );

        $this->command->info('✅ Roles created successfully!');

        // Assign All Permissions to Super Admin
        $allPermissions = Permission::pluck('name')->toArray();
        $superAdmin->givePermissionTo($allPermissions);

        // Assign Specific Permissions to Admin
        $adminPermissions = [
            'user-list', 'user-create', 'user-edit', 'user-verify',
            'course-list', 'course-edit', 'course-approve',
            'academy-list', 'academy-edit',
            'post-moderate', 'comment-moderate', 'report-view',
            'wallet-view', 'transaction-view', 'deposit-approve',
            'dashboard-view', 'analytics-view',
        ];
        $admin->givePermissionTo($adminPermissions);

        // Assign Specific Permissions to Moderator
        $moderatorPermissions = [
            'user-list',
            'course-list',
            'post-moderate', 'comment-moderate', 'report-view',
            'dashboard-view',
        ];
        $moderator->givePermissionTo($moderatorPermissions);

        // Assign Specific Permissions to Instructor
        $instructorPermissions = [
            'course-list', 'course-create', 'course-edit',
            'dashboard-view',
        ];
        $instructor->givePermissionTo($instructorPermissions);

        $this->command->info('✅ Permissions assigned to roles!');

        // Create Default Super Admin User
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@nuxnan.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'reference_code' => \Illuminate\Support\Str::uuid(),
                'personal_code' => User::generateReferralCode(),
                'email_verified_at' => now(),
                'verified' => true,
            ]
        );

        // Assign Super Admin Role
        $superAdminUser->syncRoles(['SUPER_ADMIN']);

        $this->command->info('✅ Super Admin user created!');
        $this->command->info('   Email: admin@nuxnan.com');
        $this->command->info('   Password: password');
        $this->command->line('');
        $this->command->warn('⚠️  Please change the default password after first login!');
    }
}
