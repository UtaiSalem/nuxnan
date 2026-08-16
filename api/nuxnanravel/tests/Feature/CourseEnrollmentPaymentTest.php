<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseEnrollmentPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function createCourseWithOwner()
    {
        $owner = User::factory()->create(['wallet' => 0]);
        $course = Course::factory()->create([
            'user_id' => $owner->id,
            'tuition_fees' => 10,
            'discount' => 0,
        ]);

        // Ensure settings exist (some factories create them, some don't)
        if ($course->courseSettings) {
            $course->courseSettings->update(['auto_accept_members' => 1]);
        } else {
            CourseSetting::create([
                'course_id' => $course->id,
                'auto_accept_members' => 1,
            ]);
        }

        return [$owner, $course];
    }

    public function test_enrolment_can_be_paid_with_wallet()
    {
        [$owner, $course] = $this->createCourseWithOwner();
        $buyer = User::factory()->create(['wallet' => 100, 'pp' => 0]);

        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('course.members.storemember', $course->id), [
                'payment_mode' => 'wallet',
            ]);

        $response->assertStatus(200);
        $this->assertEquals(90, (float) $buyer->fresh()->wallet);
        $this->assertEquals(0, (int) $buyer->fresh()->pp);
        $this->assertEquals(10, (float) $owner->fresh()->wallet);
    }

    public function test_enrolment_can_be_paid_with_points()
    {
        [$owner, $course] = $this->createCourseWithOwner();
        $buyer = User::factory()->create(['wallet' => 0, 'pp' => 20000]);

        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('course.members.storemember', $course->id), [
                'payment_mode' => 'points',
            ]);

        $response->assertStatus(200);
        $this->assertEquals(8000, (int) $buyer->fresh()->pp);
        $this->assertEquals(0, (float) $buyer->fresh()->wallet);
        $this->assertEquals(10, (float) $owner->fresh()->wallet);

        $this->assertDatabaseHas('points_transactions', [
            'user_id' => $buyer->id,
            'transaction_type' => 'spend',
        ]);
    }

    public function test_enrolment_can_be_paid_with_a_mix()
    {
        [$owner, $course] = $this->createCourseWithOwner();
        $buyer = User::factory()->create(['wallet' => 4, 'pp' => 20000]);

        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('course.members.storemember', $course->id), [
                'payment_mode' => 'mixed',
            ]);

        $response->assertStatus(200);
        $this->assertEquals(0, (float) $buyer->fresh()->wallet);
        $this->assertEquals(12800, (int) $buyer->fresh()->pp);
        $this->assertEquals(10, (float) $owner->fresh()->wallet);
    }

    public function test_enrolment_is_rejected_when_neither_balance_is_enough()
    {
        [$owner, $course] = $this->createCourseWithOwner();
        $buyer = User::factory()->create(['wallet' => 1, 'pp' => 100]);

        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('course.members.storemember', $course->id), [
                'payment_mode' => 'mixed',
            ]);

        $response->assertStatus(400);
        $this->assertEquals(1, (float) $buyer->fresh()->wallet);
        $this->assertEquals(100, (int) $buyer->fresh()->pp);
        $this->assertEquals(0, (float) $owner->fresh()->wallet);

        $this->assertDatabaseMissing('course_members', [
            'user_id' => $buyer->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_payment_options_endpoint_reports_what_the_learner_can_pay_with()
    {
        [$owner, $course] = $this->createCourseWithOwner();
        $buyer = User::factory()->create(['wallet' => 0, 'pp' => 20000]);

        $response = $this->actingAs($buyer, 'api')
            ->getJson(route('course.members.payment-options', $course->id));

        $response->assertStatus(200)
            ->assertJsonPath('price_thb', 10)
            ->assertJsonPath('price_points', 12000)
            ->assertJsonPath('exchange_rate', 1200)
            ->assertJsonPath('can_pay.wallet', false)
            ->assertJsonPath('can_pay.points', true);
    }
}
