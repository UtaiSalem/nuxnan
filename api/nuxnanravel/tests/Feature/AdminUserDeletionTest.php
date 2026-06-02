<?php

namespace Tests\Feature;

use App\Models\AdminUserDeletionAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles needed for the test
        \App\Models\Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        \App\Models\Role::firstOrCreate(['name' => 'STUDENT']);

        // สร้าง admin สำหรับ auth
        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $this->admin->assignRole('SUPER_ADMIN');

        // สร้าง user ที่จะลบ
        $this->targetUser = User::factory()->create(['email' => 'target@test.com']);
    }

    /** @test */
    public function admin_can_soft_delete_normal_user(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/users/{$this->targetUser->id}", [
                'reason' => 'ละเมิดกฎของระบบ',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // ตรวจ deleted_at set แล้ว
        $this->assertSoftDeleted('users', ['id' => $this->targetUser->id]);

        // ตรวจ email ถูก anonymize
        $this->assertDatabaseHas('users', [
            'id'    => $this->targetUser->id,
            'email' => "deleted_{$this->targetUser->id}@nuxnan.del",
        ]);

        // ตรวจ audit สร้างแล้ว
        $this->assertDatabaseHas('admin_user_deletion_audits', [
            'deleted_user_id' => $this->targetUser->id,
            'mode'            => 'soft_delete',
        ]);
    }

    /** @test */
    public function cannot_delete_yourself(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/users/{$this->admin->id}", [
                'reason' => 'test',
            ]);

        $response->assertStatus(403);
        $this->assertNull($this->admin->fresh()->deleted_at);
    }

    /** @test */
    public function cannot_delete_without_reason(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/users/{$this->targetUser->id}", []);

        $response->assertStatus(422);
        $this->assertNull($this->targetUser->fresh()->deleted_at);
    }

    /** @test */
    public function deleted_user_cannot_login(): void
    {
        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/users/{$this->targetUser->id}", [
                'reason' => 'ทดสอบระบบ',
            ]);

        // User soft deleted → auth:api ควร reject
        $loginResponse = $this->postJson('/api/login', [
            'login'    => 'target@test.com', // email เดิม (anonymized แล้ว)
            'password' => 'password',
        ]);

        // คาดหวัง 401 หรือ 422 (ขึ้นกับ logic ของ AuthController)
        // หมายเหตุ: ในโปรเจคนี้อาจจะคืน 401 Unauthorized
        $loginResponse->assertStatus(401);
    }

    /** @test */
    public function get_impact_returns_correct_structure(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/users/{$this->targetUser->id}/delete-impact");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['blockers', 'warnings', 'can_delete', 'summary'],
            ]);
    }

    /** @test */
    public function admin_can_restore_soft_deleted_user(): void
    {
        // Soft delete ก่อน
        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/users/{$this->targetUser->id}", [
                'reason' => 'ทดสอบ restore',
            ]);

        $this->assertSoftDeleted('users', ['id' => $this->targetUser->id]);

        // Restore
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/users/{$this->targetUser->id}/restore");

        $response->assertStatus(200)->assertJson(['success' => true]);

        // ตรวจว่า deleted_at หาย
        $this->assertDatabaseHas('users', [
            'id'         => $this->targetUser->id,
            'deleted_at' => null,
        ]);

        // ตรวจ audit restore
        $this->assertDatabaseHas('admin_user_deletion_audits', [
            'deleted_user_id' => $this->targetUser->id,
            'mode'            => 'restore',
        ]);
    }

    /** @test */
    public function wallet_transactions_remain_after_soft_delete(): void
    {
        // สร้าง wallet transaction ให้ target user
        \DB::table('wallet_transactions')->insert([
            'user_id'    => $this->targetUser->id,
            'transaction_type' => 'deposit',
            'amount'     => 100,
            'balance_before' => 0,
            'balance_after'  => 100,
            'status'     => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/users/{$this->targetUser->id}", [
                'reason' => 'soft delete test',
            ]);

        // เนื่องจากเป็น soft delete (row ใน users ยังอยู่)
        // DB cascade จะไม่ทำงาน ดังนั้น wallet_transactions ยังต้องอยู่
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->targetUser->id,
        ]);
    }

    /** @test */
    public function orphan_courses_remain_after_delete(): void
    {
        // สร้าง course ของ target user (ไม่มี FK cascade)
        \DB::table('courses')->insert([
            'user_id'    => $this->targetUser->id,
            'instructor_id' => 1,
            'name'      => 'Test Course',
            'slug'       => 'test-course-' . $this->targetUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/users/{$this->targetUser->id}", [
                'reason' => 'orphan test',
            ]);

        // Courses ยังอยู่ใน DB — documented known limitation
        $this->assertDatabaseHas('courses', [
            'user_id' => $this->targetUser->id,
        ]);
    }
}
