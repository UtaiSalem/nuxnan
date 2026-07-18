<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Services\CourseDonateService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseDonationFlagTest extends TestCase
{
    use RefreshDatabase;

    private function setupDonation(?bool $override = null): array
    {
        $owner = User::factory()->create();
        $donor = User::factory()->create(['pp' => 500]);
        $course = Course::factory()->create(['user_id' => $owner->id, 'donation_enabled' => $override]);

        return [$donor, $course];
    }

    public function test_flag_off_globally_blocks_donation(): void
    {
        config(['platform.course_donation.enabled' => false]);
        [$donor, $course] = $this->setupDonation();

        $this->expectException(DomainException::class);
        app(CourseDonateService::class)->createPointDonation($donor, $course, 100, [], null);
    }

    public function test_course_override_true_allows_donation_even_when_global_off(): void
    {
        config(['platform.course_donation.enabled' => false]);
        [$donor, $course] = $this->setupDonation(true);

        $this->assertNotNull(app(CourseDonateService::class)->createPointDonation($donor, $course, 100, [], null));
    }

    public function test_course_override_false_blocks_donation_even_when_global_on(): void
    {
        config(['platform.course_donation.enabled' => true]);
        [$donor, $course] = $this->setupDonation(false);

        $this->expectException(DomainException::class);
        app(CourseDonateService::class)->createPointDonation($donor, $course, 100, [], null);
    }

    public function test_course_null_falls_back_to_global(): void
    {
        config(['platform.course_donation.enabled' => true]);
        [$donor, $course] = $this->setupDonation();

        $this->assertNotNull(app(CourseDonateService::class)->createPointDonation($donor, $course, 100, [], null));
    }
}
