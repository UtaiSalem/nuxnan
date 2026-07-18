<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseDonate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCourseDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_list_courses_without_auth(): void
    {
        $c = Course::factory()->create(['donation_enabled' => true]);
        $this->getJson('/api/public/courses')->assertOk()->assertJsonPath('data.0.id', $c->id);
    }

    public function test_disabled_courses_are_excluded_from_public_list(): void
    {
        Course::factory()->create(['donation_enabled' => false]);
        $this->getJson('/api/public/courses')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_search_by_name_returns_matching_courses(): void
    {
        $c = Course::factory()->create(['name' => 'Supportable Algebra']);
        Course::factory()->create(['name' => 'History']);
        $this->getJson('/api/public/courses?q=Algebra')->assertJsonPath('data.0.id', $c->id);
    }

    public function test_sort_most_supported_orders_by_donation_sum(): void
    {
        $a = Course::factory()->create();
        $b = Course::factory()->create();
        CourseDonate::create(['course_id' => $a->id, 'donation_type' => 'point', 'points_amount' => 5, 'status' => 'completed']);
        CourseDonate::create(['course_id' => $b->id, 'donation_type' => 'point', 'points_amount' => 20, 'status' => 'approved']);
        $this->getJson('/api/public/courses?sort=most_supported')->assertJsonPath('data.0.id', $b->id);
    }

    public function test_show_returns_donation_signals(): void
    {
        $c = Course::factory()->create();
        CourseDonate::create(['course_id' => $c->id, 'donation_type' => 'point', 'points_amount' => 7, 'status' => 'completed']);
        $this->getJson('/api/public/courses/'.$c->slug)->assertOk()->assertJsonPath('data.total_donated_points', 7);
    }

    public function test_show_404_when_donation_disabled(): void
    {
        $c = Course::factory()->create(['donation_enabled' => false]);
        $this->getJson('/api/public/courses/'.$c->slug)->assertNotFound();
    }

    public function test_support_summary_aggregates_correctly(): void
    {
        $c = Course::factory()->create();
        $u = User::factory()->create();
        CourseDonate::create(['course_id' => $c->id, 'donor_id' => $u->id, 'donation_type' => 'mixed', 'points_amount' => 4, 'cash_amount' => 12.5, 'status' => 'completed']);
        $this->getJson('/api/public/courses/'.$c->slug.'/support-summary')->assertJsonPath('data.total_donated_points', 4)->assertJsonPath('data.total_donated_cash', 12.5)->assertJsonPath('data.total_donors', 1);
    }

    public function test_anonymous_donor_shows_masked_name(): void
    {
        $c = Course::factory()->create();
        CourseDonate::create(['course_id' => $c->id, 'donor_display_name' => 'Secret Name', 'donation_type' => 'point', 'points_amount' => 1, 'status' => 'completed', 'anonymous' => true]);
        $this->getJson('/api/public/courses/'.$c->slug.'/support-summary')->assertJsonPath('data.recent_donors.0.display_name', 'Anonymous donor')->assertJsonMissing(['display_name' => 'Secret Name']);
    }
}
