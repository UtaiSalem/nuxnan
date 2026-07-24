<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyDonate;
use App\Models\Course;
use App\Models\CourseDonate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuestDonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_cash_donations_without_a_donor(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create(['user_id' => User::factory(), 'donation_enabled' => true, 'status' => 1]);
        $academy = Academy::factory()->create(['user_id' => User::factory(), 'donation_enabled' => true]);
        $this->post("/api/courses/{$course->id}/donations/cash", $this->cashPayload())->assertCreated();
        $this->post("/api/academies/{$academy->id}/donations/cash", $this->cashPayload())->assertCreated();
        $this->assertDatabaseHas('course_donates', ['course_id' => $course->id, 'donor_id' => null, 'status' => CourseDonate::STATUS_PENDING]);
        $this->assertDatabaseHas('academy_donates', ['academy_id' => $academy->id, 'donor_id' => null, 'status' => AcademyDonate::STATUS_PENDING]);
    }

    public function test_guest_cannot_submit_points_donations(): void
    {
        $course = Course::factory()->create(['user_id' => User::factory(), 'donation_enabled' => true, 'status' => 1]);
        $academy = Academy::factory()->create(['user_id' => User::factory(), 'donation_enabled' => true]);
        $this->postJson("/api/courses/{$course->id}/donations/points", ['points_amount' => 1])->assertUnauthorized();
        $this->postJson("/api/academies/{$academy->id}/donations/points", ['points_amount' => 1])->assertUnauthorized();
    }

    public function test_guest_cannot_donate_to_disabled_or_private_beneficiaries(): void
    {
        $owner = User::factory()->create();
        $disabledCourse = Course::factory()->create(['user_id' => $owner->id, 'donation_enabled' => false, 'status' => 1]);
        $privateCourse = Course::factory()->create(['user_id' => $owner->id, 'donation_enabled' => true, 'status' => 2]);
        $disabledAcademy = Academy::factory()->create(['user_id' => $owner->id, 'donation_enabled' => false]);
        $this->post("/api/courses/{$disabledCourse->id}/donations/cash", $this->cashPayload())->assertForbidden();
        $this->post("/api/courses/{$privateCourse->id}/donations/cash", $this->cashPayload())->assertForbidden();
        $this->post("/api/academies/{$disabledAcademy->id}/donations/cash", $this->cashPayload())->assertForbidden();
    }

    public function test_authenticated_user_can_still_submit_cash_donation(): void
    {
        Storage::fake('local');
        $donor = User::factory()->create();
        $course = Course::factory()->create(['user_id' => User::factory(), 'donation_enabled' => true, 'status' => 1]);
        $this->actingAs($donor, 'api')->post("/api/courses/{$course->id}/donations/cash", $this->cashPayload())->assertCreated();
        $this->assertDatabaseHas('course_donates', ['course_id' => $course->id, 'donor_id' => $donor->id]);
    }

    public function test_guest_cash_route_throttles_after_six_requests(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create(['user_id' => User::factory(), 'donation_enabled' => true, 'status' => 1]);
        $responses = [];
        for ($i = 0; $i < 7; $i++) {
            $responses[] = $this->post("/api/courses/{$course->id}/donations/cash", $this->cashPayload());
        }
        $this->assertSame(429, $responses[6]->status());
    }

    private function cashPayload(): array
    {
        return ['cash_amount' => 10, 'payment_method' => 'bank_transfer', 'slip' => UploadedFile::fake()->image('slip.jpg')];
    }
}
