<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CoursePurchase;
use App\Models\CourseQuizResult;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserAnswerQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseMemberRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_leave_free_course()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 1,
            'course_member_status' => 1,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/courses/{$course->id}/members/{$member->id}/remove", [
                'mode' => 'self_leave',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('course_members', ['id' => $member->id]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => Notification::TYPE_COURSE_SELF_LEFT,
        ]);
    }

    public function test_self_leave_paid_course_no_refund()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create(['wallet' => 1000]);
        $course = Course::factory()->create(['user_id' => $admin->id, 'tuition_fees' => 500]);

        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 1,
            'course_member_status' => 1,
        ]);

        // Mock a purchase
        $p = new CoursePurchase;
        $p->purchase_type = 'enrollment';
        $p->source_course_id = $course->id;
        $p->buyer_id = $user->id;
        $p->seller_id = $admin->id;
        $p->course_member_id = $member->id;
        $p->amount_wallet = 500;
        $p->payment_mode = 'wallet';
        $p->status = 'completed';
        $p->paid_at = now();
        $p->save();

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/courses/{$course->id}/members/{$member->id}/remove", [
                'mode' => 'self_leave',
            ]);

        $response->assertStatus(200);
        $this->assertEquals(1000, $user->fresh()->wallet);
        $this->assertDatabaseHas('course_purchases', [
            'id' => $p->id,
            'status' => 'completed',
        ]);
    }

    public function test_admin_remove_paid_course_with_refund()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create(['wallet' => 0]);
        $course = Course::factory()->create(['user_id' => $admin->id, 'tuition_fees' => 500]);

        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 1,
            'course_member_status' => 1,
        ]);

        // Mock a purchase
        $p = new CoursePurchase;
        $p->purchase_type = 'enrollment';
        $p->source_course_id = $course->id;
        $p->buyer_id = $user->id;
        $p->seller_id = $admin->id;
        $p->course_member_id = $member->id;
        $p->amount_wallet = 500;
        $p->wallet_transaction_id = 1;
        $p->payment_mode = 'wallet';
        $p->status = 'completed';
        $p->paid_at = now();
        $p->save();

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/courses/{$course->id}/members/{$member->id}/remove", [
                'mode' => 'admin_remove',
                'reason' => 'Test refund',
            ]);

        $response->assertStatus(200);
        $this->assertEquals(500, $user->fresh()->wallet);
        $this->assertDatabaseHas('course_purchases', [
            'id' => $p->id,
            'status' => 'refunded',
        ]);
    }

    public function test_unauthorized_removal()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create();

        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user2->id,
            'status' => 1,
            'course_member_status' => 1,
        ]);

        // User 1 tries to remove User 2
        $response = $this->actingAs($user1, 'api')
            ->postJson("/api/courses/{$course->id}/members/{$member->id}/remove", [
                'mode' => 'admin_remove',
            ]);

        $response->assertStatus(400);
        $this->assertDatabaseHas('course_members', ['id' => $member->id]);
    }

    public function test_removal_cleans_up_related_data()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $admin->id]);

        $member = CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 1,
            'course_member_status' => 1,
        ]);

        // Create related data
        CourseQuizResult::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'quiz_id' => 1,
            'score' => 10,
        ]);

        UserAnswerQuestion::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'quiz_id' => 1,
            'question_id' => 1,
            'answer_id' => 1,
            'correct_option_id' => 1,
            'points' => 0,
        ]);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/courses/{$course->id}/members/{$member->id}/remove", [
                'mode' => 'admin_remove',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('course_quiz_results', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('user_answer_questions', ['user_id' => $user->id]);
    }
}
