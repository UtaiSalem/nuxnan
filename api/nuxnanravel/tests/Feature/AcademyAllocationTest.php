<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyPointAccount;
use App\Models\AcademyPointTransaction;
use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\CoursePointTransaction;
use App\Models\User;
use App\Services\AcademyAllocationService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyAllocationTest extends TestCase
{
    use RefreshDatabase;

    private function setupAllocation(int $balance = 1000): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        $course = Course::factory()->create(['academy_id' => $academy->id]);
        AcademyPointAccount::create(['academy_id' => $academy->id, 'balance' => $balance]);

        return [$owner, $academy, $course];
    }

    public function test_academy_owner_can_allocate_to_own_course(): void
    {
        [$owner, $academy, $course] = $this->setupAllocation();
        $result = app(AcademyAllocationService::class)->allocateToCourse($owner, $academy, $course, 250, 'course support', null);
        $this->assertSame(750, AcademyPointAccount::first()->fresh()->balance);
        $this->assertSame(250, CoursePointAccount::first()->fresh()->balance);
        $this->assertDatabaseHas('academy_point_transactions', ['id' => $result['academy_tx']->id, 'type' => AcademyPointTransaction::TYPE_ALLOCATION_OUT]);
        $this->assertDatabaseHas('course_point_transactions', ['id' => $result['course_tx']->id, 'type' => CoursePointTransaction::TYPE_ALLOCATION_IN]);
    }

    public function test_allocation_is_idempotent_on_replay(): void
    {
        [$owner, $academy, $course] = $this->setupAllocation();
        $service = app(AcademyAllocationService::class);
        $first = $service->allocateToCourse($owner, $academy, $course, 100, null, 'allocation-replay');
        $second = $service->allocateToCourse($owner, $academy, $course, 100, null, 'allocation-replay');
        $this->assertSame($first['academy_tx']->id, $second['academy_tx']->id);
        $this->assertSame(100, CoursePointAccount::first()->fresh()->balance);
        $this->assertSame(1, AcademyPointTransaction::count());
    }

    public function test_allocate_to_course_from_different_academy_throws(): void
    {
        [$owner, $academy, $course] = $this->setupAllocation();
        $other = Academy::factory()->create(['user_id' => $owner->id]);
        $foreignCourse = Course::factory()->create(['academy_id' => $other->id]);
        $this->expectException(DomainException::class);
        app(AcademyAllocationService::class)->allocateToCourse($owner, $academy, $foreignCourse, 1, null, null);
    }

    public function test_amount_exceeding_balance_throws(): void
    {
        [$owner, $academy, $course] = $this->setupAllocation(10);
        $this->expectException(DomainException::class);
        app(AcademyAllocationService::class)->allocateToCourse($owner, $academy, $course, 11, null, null);
    }

    public function test_non_admin_returns_403(): void
    {
        [, $academy, $course] = $this->setupAllocation();
        $this->actingAs(User::factory()->create(), 'api')->postJson('/api/academies/'.$academy->id.'/allocations', ['course_id' => $course->id, 'amount' => 1])->assertForbidden();
    }

    public function test_endpoint_returns_201_and_creates_history_entry(): void
    {
        [$owner, $academy, $course] = $this->setupAllocation();
        $this->actingAs($owner, 'api')->postJson('/api/academies/'.$academy->id.'/allocations', ['course_id' => $course->id, 'amount' => 50])->assertCreated();
        $this->actingAs($owner, 'api')->getJson('/api/academies/'.$academy->id.'/allocations')->assertOk()->assertJsonPath('data.0.course_id', $course->id);
    }
}
