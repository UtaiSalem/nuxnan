<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyPointAccount;
use App\Models\AcademyPointTransaction;
use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\CoursePointTransaction;
use App\Models\User;
use App\Services\PointLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_donate_points_to_course_debits_user_and_credits_course(): void
    {
        $donor = User::factory()->create(['pp' => 1000]);
        $course = Course::factory()->create();
        $before = $donor->pp;

        $result = app(PointLedgerService::class)->donatePoints($donor, 'course', $course->id, 200, 'course_donation', 'course-ledger-1');

        $this->assertEquals($before - 200, (int) $donor->fresh()->pp);
        $this->assertSame(200, (int) CoursePointAccount::where('course_id', $course->id)->first()->balance);
        $this->assertSame(200, (int) CoursePointTransaction::where('type', 'donation_point_credit')->sum('amount'));
        $this->assertNotNull($result['destination_transaction_id']);
    }

    public function test_donate_points_to_academy_debits_user_and_credits_academy(): void
    {
        $donor = User::factory()->create(['pp' => 1000]);
        $academy = Academy::factory()->create();
        $before = $donor->pp;

        $result = app(PointLedgerService::class)->donatePoints($donor, 'academy', $academy->id, 150, 'academy_donation', 'academy-ledger-1');

        $this->assertEquals($before - 150, (int) $donor->fresh()->pp);
        $this->assertSame(150, (int) AcademyPointAccount::where('academy_id', $academy->id)->first()->balance);
        $this->assertSame(150, (int) AcademyPointTransaction::where('type', 'donation_point_credit')->sum('amount'));
        $this->assertNotNull($result['destination_transaction_id']);
    }

    public function test_donate_points_is_idempotent_on_same_key(): void
    {
        $donor = User::factory()->create(['pp' => 1000]);
        $course = Course::factory()->create();

        app(PointLedgerService::class)->donatePoints($donor, 'course', $course->id, 100, 'course_donation', 'idempotent-key');
        app(PointLedgerService::class)->donatePoints($donor, 'course', $course->id, 100, 'course_donation', 'idempotent-key');

        $this->assertSame(100, (int) CoursePointAccount::where('course_id', $course->id)->first()->balance);
        $this->assertSame(1, CoursePointTransaction::where('idempotency_key', 'idempotent-key')->count());
    }

    public function test_donate_points_insufficient_balance_throws(): void
    {
        $donor = User::factory()->create(['pp' => 50]);
        $course = Course::factory()->create();

        $this->expectException(\DomainException::class);
        app(PointLedgerService::class)->donatePoints($donor, 'course', $course->id, 100, 'course_donation', null);
    }

    public function test_reconcile_course_account_matches_transaction_sum(): void
    {
        $donor = User::factory()->create(['pp' => 1000]);
        $course = Course::factory()->create();
        app(PointLedgerService::class)->donatePoints($donor, 'course', $course->id, 200, 'course_donation', 'recon-1');

        $result = app(PointLedgerService::class)->reconcileCourseAccount($course->id);
        $this->assertTrue($result['balanced']);
        $this->assertSame(200, $result['computed']);
        $this->assertSame(200, $result['stored']);
    }

    public function test_reconcile_academy_account_matches_transaction_sum(): void
    {
        $donor = User::factory()->create(['pp' => 1000]);
        $academy = Academy::factory()->create();
        app(PointLedgerService::class)->donatePoints($donor, 'academy', $academy->id, 150, 'academy_donation', 'recon-a-1');

        $result = app(PointLedgerService::class)->reconcileAcademyAccount($academy->id);
        $this->assertTrue($result['balanced']);
        $this->assertSame(150, $result['computed']);
        $this->assertSame(150, $result['stored']);
    }
}
