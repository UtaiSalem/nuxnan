<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Academy;
use App\Models\AcademyAdmin;
use App\Models\CoursePurchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyCoursePurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_course_for_academy_successfully()
    {
        $seller = User::factory()->create(['wallet' => 0]);
        $buyer  = User::factory()->create(['wallet' => 1000]);
        
        $academy = Academy::factory()->create(['user_id' => $buyer->id]); // Buyer is owner

        $course = Course::factory()->create([
            'user_id'           => $seller->id,
            'price'             => 500,
            'is_for_marketplace' => true,
            'saleable'          => true,
        ]);

        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), [
                'payment_mode' => 'wallet',
                'academy_id'   => $academy->id,
            ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('course_purchases', [
            'source_course_id' => $course->id,
            'buyer_id'         => $buyer->id,
            'academy_id'       => $academy->id,
            'status'           => 'completed',
        ]);

        $purchase = CoursePurchase::where('academy_id', $academy->id)->first();
        $clonedCourse = Course::find($purchase->cloned_course_id);
        
        $this->assertNotNull($clonedCourse);
        $this->assertEquals($academy->id, $clonedCourse->academy_id);
        $this->assertEquals($buyer->id, $clonedCourse->user_id);
    }

    public function test_purchase_course_for_academy_as_admin()
    {
        $owner  = User::factory()->create();
        $admin  = User::factory()->create(['wallet' => 1000]);
        $academy = Academy::factory()->create(['user_id' => $owner->id]);
        
        AcademyAdmin::create([
            'academy_id' => $academy->id,
            'user_id'    => $admin->id,
        ]);

        $course = Course::factory()->create([
            'price'             => 500,
            'is_for_marketplace' => true,
            'saleable'          => true,
        ]);

        $response = $this->actingAs($admin, 'api')
            ->postJson(route('courses.purchase', $course->id), [
                'payment_mode' => 'wallet',
                'academy_id'   => $academy->id,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('course_purchases', ['academy_id' => $academy->id]);
    }

    public function test_purchase_for_unauthorized_academy_is_rejected()
    {
        $buyer  = User::factory()->create(['wallet' => 1000]);
        $academy = Academy::factory()->create(); // Someone else's academy

        $course = Course::factory()->create([
            'is_for_marketplace' => true,
            'saleable'          => true,
        ]);

        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), [
                'payment_mode' => 'wallet',
                'academy_id'   => $academy->id,
            ]);

        $response->assertStatus(403);
        $this->assertStringContainsString('ไม่มีสิทธิ์', $response->json('message'));
    }

    public function test_duplicate_purchase_for_same_academy_is_rejected()
    {
        $buyer  = User::factory()->create(['wallet' => 2000]);
        $academy = Academy::factory()->create(['user_id' => $buyer->id]);
        
        $course = Course::factory()->create([
            'price'             => 100,
            'is_for_marketplace' => true,
            'saleable'          => true,
        ]);

        // First purchase
        $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), [
                'payment_mode' => 'wallet',
                'academy_id'   => $academy->id,
            ])
            ->assertStatus(200);

        // Second purchase for same academy
        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), [
                'payment_mode' => 'wallet',
                'academy_id'   => $academy->id,
            ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('โรงเรียนมีคอร์สนี้อยู่แล้ว', $response->json('message'));
    }

    public function test_purchase_for_different_academy_is_allowed()
    {
        $buyer  = User::factory()->create(['wallet' => 2000]);
        $academy1 = Academy::factory()->create(['user_id' => $buyer->id]);
        $academy2 = Academy::factory()->create(['user_id' => $buyer->id]);
        
        $course = Course::factory()->create([
            'price'             => 100,
            'is_for_marketplace' => true,
            'saleable'          => true,
        ]);

        // Purchase for academy 1
        $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), [
                'payment_mode' => 'wallet',
                'academy_id'   => $academy1->id,
            ])
            ->assertStatus(200);

        // Purchase for academy 2 should be allowed
        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), [
                'payment_mode' => 'wallet',
                'academy_id'   => $academy2->id,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(2, CoursePurchase::count());
    }

    public function test_purchase_for_academy_when_personally_owned_is_allowed()
    {
        $buyer  = User::factory()->create(['wallet' => 2000]);
        $academy = Academy::factory()->create(['user_id' => $buyer->id]);
        
        $course = Course::factory()->create([
            'price'             => 100,
            'is_for_marketplace' => true,
            'saleable'          => true,
        ]);

        // Personal purchase
        $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), [
                'payment_mode' => 'wallet',
            ])
            ->assertStatus(200);

        // Academy purchase should be allowed
        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), [
                'payment_mode' => 'wallet',
                'academy_id'   => $academy->id,
            ]);

        $response->assertStatus(200);
    }
}
