<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\User;
use App\Models\XpEvent;
use App\Models\SchoolXpCycle;
use App\Models\ClassroomPointCycle;
use App\Services\Gamification\XpService;
use App\Services\Gamification\ClassroomPointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolGamificationTest extends TestCase
{
    use RefreshDatabase;

    protected Academy $academy;
    protected User $user;
    protected XpService $xpService;
    protected ClassroomPointsService $classroomPointsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->academy = Academy::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->xpService = app(XpService::class);
        $this->classroomPointsService = app(ClassroomPointsService::class);
    }

    public function test_xp_service_awards_xp_and_calculates_levels()
    {
        // Award some XP
        $event = $this->xpService->award(
            $this->academy,
            'post.created',
            1500,
            $this->user->id,
            null,
            ['some_key' => 'some_val']
        );

        $this->assertDatabaseHas('xp_events', [
            'id' => $event->id,
            'academy_id' => $this->academy->id,
            'user_id' => $this->user->id,
            'xp' => 1500,
            'classroom_pts' => 0,
        ]);

        // Level = floor(sqrt(1500 / 1000)) = floor(sqrt(1.5)) = 1
        $this->assertDatabaseHas('school_xp_cycles', [
            'academy_id' => $this->academy->id,
            'cycle_type' => 'all_time',
            'cycle_key' => 'all',
            'total_xp' => 1500,
            'level' => 1,
        ]);

        // Let's add more XP to reach level 2 (4000 XP)
        $this->xpService->award($this->academy, 'course.completed', 3000, $this->user->id);

        $this->assertDatabaseHas('school_xp_cycles', [
            'academy_id' => $this->academy->id,
            'cycle_type' => 'all_time',
            'cycle_key' => 'all',
            'total_xp' => 4500,
            'level' => 2, // floor(sqrt(4500 / 1000)) = floor(2.12) = 2
        ]);
    }

    public function test_classroom_points_service_awards_points_and_generates_leaderboard()
    {
        $classroomA = AcademyGroup::create([
            'academy_id' => $this->academy->id,
            'name' => 'ห้อง ม.1/1',
            'type' => 'classroom',
        ]);

        $classroomB = AcademyGroup::create([
            'academy_id' => $this->academy->id,
            'name' => 'ห้อง ม.1/2',
            'type' => 'classroom',
        ]);

        // Award points to A
        $this->classroomPointsService->award($classroomA, 'attendance.checkin', 100, $this->user->id);

        // Award points to B
        $this->classroomPointsService->award($classroomB, 'attendance.checkin', 250, $this->user->id);

        $this->assertDatabaseHas('classroom_point_cycles', [
            'classroom_group_id' => $classroomA->id,
            'total_points' => 100,
        ]);

        $this->assertDatabaseHas('classroom_point_cycles', [
            'classroom_group_id' => $classroomB->id,
            'total_points' => 250,
        ]);

        // Fetch leaderboard
        $leaderboard = $this->classroomPointsService->leaderboard($this->academy->id, 'month');

        $this->assertCount(2, $leaderboard);
        // Rank 1 should be classroom B
        $this->assertEquals($classroomB->id, $leaderboard[0]['group_id']);
        $this->assertEquals(250, $leaderboard[0]['points']);
        // Rank 2 should be classroom A
        $this->assertEquals($classroomA->id, $leaderboard[1]['group_id']);
        $this->assertEquals(100, $leaderboard[1]['points']);
    }

    public function test_endpoints_return_correct_json_structures()
    {
        $classroom = AcademyGroup::create([
            'academy_id' => $this->academy->id,
            'name' => 'ห้อง ม.1/1',
            'type' => 'classroom',
        ]);

        // Award XP & Classroom points
        $this->xpService->award($this->academy, 'post.created', 1200, $this->user->id);
        $this->classroomPointsService->award($classroom, 'attendance.checkin', 50, $this->user->id);

        $this->actingAs($this->user, 'api');

        // Test Summary endpoint
        $response = $this->getJson("/api/academies/{$this->academy->id}/gamification/summary");
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'cycle_type' => 'all_time',
                    'level' => 1,
                    'total_xp' => 1200,
                ],
            ]);

        // Test Leaderboard endpoint
        $response = $this->getJson("/api/academies/{$this->academy->id}/gamification/leaderboard?cycle=month");
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data');

        // Test Recent Events endpoint
        $response = $this->getJson("/api/academies/{$this->academy->id}/gamification/recent");
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'academy_id',
                            'source',
                            'xp',
                            'classroom_pts',
                            'occurred_at',
                        ],
                    ],
                ],
            ]);
    }

    public function test_observers_automatically_award_xp_on_post_like_comment()
    {
        // 1. Create a Post -> Awards 10 XP
        $post = \App\Models\AcademyPost::create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->user->id,
            'content' => 'Hello nuxnan social school hub!',
        ]);

        $this->assertDatabaseHas('school_xp_cycles', [
            'academy_id' => $this->academy->id,
            'total_xp' => 10,
        ]);

        // 2. Add a Like -> Awards 1 XP to the post author
        $liker = User::factory()->create();
        $like = new \App\Models\AcademyPostLike();
        $like->academy_post_id = $post->id;
        $like->user_id = $liker->id;
        $like->save();

        $this->assertDatabaseHas('school_xp_cycles', [
            'academy_id' => $this->academy->id,
            'total_xp' => 11, // 10 + 1
        ]);

        // 3. Add a Comment -> Awards 2 XP to the post author
        $commenter = User::factory()->create();
        \App\Models\AcademyPostComment::create([
            'academy_post_id' => $post->id,
            'user_id' => $commenter->id,
            'content' => 'Keep up the good work!',
        ]);

        $this->assertDatabaseHas('school_xp_cycles', [
            'academy_id' => $this->academy->id,
            'total_xp' => 13, // 11 + 2
        ]);
    }
}
