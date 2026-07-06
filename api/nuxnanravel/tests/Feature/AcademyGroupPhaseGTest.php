<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupMember;
use App\Models\AcademyGroupPermission;
use App\Models\AcademyMember;
use App\Models\AcademyPost;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyGroupPhaseGTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $student;

    protected $academy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'username' => 'admin_user_'.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
            'pp' => 1000,
        ]);

        $this->student = User::create([
            'name' => 'Student User',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'username' => 'student_user_'.uniqid(),
            'reference_code' => 'R2'.uniqid(),
            'personal_code' => 'P2'.uniqid(),
            'pp' => 1000,
        ]);

        $this->academy = Academy::create([
            'user_id' => $this->admin->id,
            'name' => 'Test Academy',
            'type' => 'school',
        ]);

        // Approve admin as academy member
        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->admin->id,
            'role' => 'admin',
            'status' => 2, // Approved
        ]);
    }

    /** @test */
    public function can_get_academy_group_types()
    {
        $response = $this->getJson('/api/academy-group-types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['key', 'label', 'label_en', 'icon', 'color', 'order'],
                ],
            ]);
    }

    /** @test */
    public function can_get_academy_group_permissions()
    {
        $response = $this->getJson('/api/academy-group-permissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['key', 'label', 'default'],
                ],
            ]);
    }

    /** @test */
    public function store_seeding_works_and_supports_parent_id()
    {
        $parentGroup = AcademyGroup::create([
            'academy_id' => $this->academy->id,
            'name' => 'Parent Office',
            'type' => 'office',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/academies/{$this->academy->id}/groups", [
                'name' => 'Sub Department',
                'type' => 'department',
                'parent_id' => $parentGroup->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('group.parent_id', $parentGroup->id);

        $groupId = $response->json('group.id');

        // Check seeded permissions
        $this->assertDatabaseCount('academy_group_permissions', 6);
        $this->assertDatabaseHas('academy_group_permissions', [
            'academy_group_id' => $groupId,
            'permission_key' => 'can_post',
            'enabled' => false,
        ]);
    }

    /** @test */
    public function add_member_checks_academy_member_status()
    {
        $group = AcademyGroup::create([
            'academy_id' => $this->academy->id,
            'name' => 'Test Club',
            'type' => 'club',
        ]);

        // Student is not academy member yet
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/academies/groups/{$group->id}/members", [
                'user_id' => $this->student->id,
                'role' => 'student',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'ผู้ใช้รายนี้ยังไม่ได้เป็นสมาชิกที่ได้รับการอนุมัติของสถาบันการศึกษา');

        // Link student with pending status
        $member = AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->student->id,
            'role' => 'student',
            'status' => 1, // Pending
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/academies/groups/{$group->id}/members", [
                'user_id' => $this->student->id,
                'role' => 'student',
            ]);

        $response->assertStatus(422);

        // Approve student membership
        $member->update(['status' => 2]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/academies/groups/{$group->id}/members", [
                'user_id' => $this->student->id,
                'role' => 'student',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('academy_group_members', [
            'academy_group_id' => $group->id,
            'user_id' => $this->student->id,
        ]);
    }

    /** @test */
    public function can_manage_group_admins_via_api_routes()
    {
        $group = AcademyGroup::create([
            'academy_id' => $this->academy->id,
            'name' => 'Admin Group',
            'type' => 'department',
        ]);

        AcademyMember::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->student->id,
            'role' => 'student',
            'status' => 2,
        ]);

        $createResponse = $this->actingAs($this->admin, 'api')
            ->postJson("/api/academies/groups/{$group->id}/admins", [
                'user_id' => $this->student->id,
                'role' => 'leader',
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('admin.user_id', $this->student->id)
            ->assertJsonPath('admin.role', 'leader');

        $this->assertDatabaseHas('academy_group_admins', [
            'academy_group_id' => $group->id,
            'user_id' => $this->student->id,
            'role' => 'leader',
        ]);

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/academies/groups/{$group->id}/admins")
            ->assertStatus(200)
            ->assertJsonCount(1, 'admins');

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/academies/groups/{$group->id}/admins/role", [
                'user_id' => $this->student->id,
                'role' => 'advisor',
            ])
            ->assertStatus(200)
            ->assertJsonPath('admin.role', 'advisor');

        $this->assertDatabaseHas('academy_group_admins', [
            'academy_group_id' => $group->id,
            'user_id' => $this->student->id,
            'role' => 'advisor',
        ]);

        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/academies/groups/{$group->id}/admins", [
                'user_id' => $this->student->id,
            ])
            ->assertStatus(200);

        $this->assertDatabaseMissing('academy_group_admins', [
            'academy_group_id' => $group->id,
            'user_id' => $this->student->id,
        ]);
    }

    /** @test */
    public function post_as_group_validation_rules()
    {
        $group = AcademyGroup::create([
            'academy_id' => $this->academy->id,
            'name' => 'Test Club',
            'type' => 'club',
        ]);

        // 1. Post by non-member of group should fail
        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/academies/{$this->academy->id}/posts", [
                'content' => 'Hello group!',
                'posted_as_group_id' => $group->id,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'คุณไม่ใช่สมาชิกของส่วนงานนี้');

        // Make student a group member
        AcademyGroupMember::create([
            'academy_group_id' => $group->id,
            'user_id' => $this->student->id,
            'role' => 'student',
        ]);

        // 2. Post by member of group but permission not enabled should fail (Strict default)
        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/academies/{$this->academy->id}/posts", [
                'content' => 'Hello group!',
                'posted_as_group_id' => $group->id,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'ส่วนงานนี้ยังไม่ได้เปิดสิทธิ์โพสต์');

        // Enable can_post permission
        AcademyGroupPermission::create([
            'academy_group_id' => $group->id,
            'permission_key' => 'can_post',
            'enabled' => true,
        ]);

        // 3. Post should now succeed
        $response = $this->actingAs($this->student, 'api')
            ->postJson("/api/academies/{$this->academy->id}/posts", [
                'content' => 'Hello group!',
                'posted_as_group_id' => $group->id,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('academy_posts', [
            'content' => 'Hello group!',
            'posted_as_group_id' => $group->id,
        ]);
    }

    /** @test */
    public function group_mute_and_feed_exclude()
    {
        $group1 = AcademyGroup::create([
            'academy_id' => $this->academy->id,
            'name' => 'Group 1',
            'type' => 'club',
        ]);

        $group2 = AcademyGroup::create([
            'academy_id' => $this->academy->id,
            'name' => 'Group 2',
            'type' => 'club',
        ]);

        // Make admin a member of both
        AcademyGroupMember::create(['academy_group_id' => $group1->id, 'user_id' => $this->admin->id]);
        AcademyGroupMember::create(['academy_group_id' => $group2->id, 'user_id' => $this->admin->id]);

        // Enable posting
        AcademyGroupPermission::create(['academy_group_id' => $group1->id, 'permission_key' => 'can_post', 'enabled' => true]);
        AcademyGroupPermission::create(['academy_group_id' => $group2->id, 'permission_key' => 'can_post', 'enabled' => true]);

        // Create posts
        $post1 = AcademyPost::create(['user_id' => $this->admin->id, 'academy_id' => $this->academy->id, 'content' => 'Post from G1', 'posted_as_group_id' => $group1->id]);
        $post2 = AcademyPost::create(['user_id' => $this->admin->id, 'academy_id' => $this->academy->id, 'content' => 'Post from G2', 'posted_as_group_id' => $group2->id]);
        $post3 = AcademyPost::create(['user_id' => $this->admin->id, 'academy_id' => $this->academy->id, 'content' => 'Post from Self']);

        // Associate activity wrapper
        $act1 = Activity::create(['user_id' => $this->admin->id, 'activity_type' => ActivityType::CREATE_POST->value, 'activityable_id' => $post1->id, 'activityable_type' => AcademyPost::class]);
        $act2 = Activity::create(['user_id' => $this->admin->id, 'activity_type' => ActivityType::CREATE_POST->value, 'activityable_id' => $post2->id, 'activityable_type' => AcademyPost::class]);
        $act3 = Activity::create(['user_id' => $this->admin->id, 'activity_type' => ActivityType::CREATE_POST->value, 'activityable_id' => $post3->id, 'activityable_type' => AcademyPost::class]);

        // Call feed before mute
        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/academies/{$this->academy->name}/feeds");

        $response->assertStatus(200);
        $activities = $response->json('activities');
        $this->assertCount(3, $activities);

        // Mute Group 1
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/academies/groups/{$group1->id}/mute");

        $response->assertStatus(200)->assertJsonPath('muted', true);
        $this->assertDatabaseHas('user_muted_groups', [
            'user_id' => $this->admin->id,
            'academy_group_id' => $group1->id,
        ]);

        // Call feed after mute -> should not see G1 post (only G2 and Self)
        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/academies/{$this->academy->name}/feeds");

        $response->assertStatus(200);
        $activities = $response->json('activities');
        $this->assertCount(2, $activities);

        $contents = collect($activities)->pluck('target_resource.content');
        $this->assertContains('Post from G2', $contents);
        $this->assertContains('Post from Self', $contents);
        $this->assertNotContains('Post from G1', $contents);

        // Unmute Group 1
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/academies/groups/{$group1->id}/mute");

        $response->assertStatus(200)->assertJsonPath('muted', false);

        // Call feed after unmute -> should see all 3 again
        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/academies/{$this->academy->name}/feeds");

        $activities = $response->json('activities');
        $this->assertCount(3, $activities);
    }
}
