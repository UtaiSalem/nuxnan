<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseDonate;
use App\Models\CoursePointAccount;
use App\Models\CoursePointTransaction;
use App\Models\User;
use App\Services\CourseDonateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseDonateTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(User $owner): Course
    {
        return Course::factory()->create(['user_id' => $owner->id]);
    }

    public function test_point_donation_deducts_donor_and_credits_course(): void
    {
        $owner = User::factory()->create();
        $donor = User::factory()->create(['pp' => 500]);
        $course = $this->makeCourse($owner);
        $service = app(CourseDonateService::class);

        $donation = $service->createPointDonation($donor, $course, 100, [], null);

        $this->assertSame(400, (int) $donor->fresh()->pp);
        $this->assertSame(100, (int) CoursePointAccount::where('course_id', $course->id)->first()->balance);
        $this->assertSame(CourseDonate::STATUS_COMPLETED, $donation->status);
        $this->assertNotNull($donation->course_point_transaction_id);
        $this->assertSame(1, CoursePointTransaction::where('type', 'donation_point_credit')->count());
    }

    public function test_point_donation_is_idempotent_on_replay(): void
    {
        $owner = User::factory()->create();
        $donor = User::factory()->create(['pp' => 500]);
        $course = $this->makeCourse($owner);
        $service = app(CourseDonateService::class);

        $a = $service->createPointDonation($donor, $course, 50, [], 'idem-key-1');
        $b = $service->createPointDonation($donor, $course, 50, [], 'idem-key-1');

        $this->assertSame($a->id, $b->id);
        $this->assertSame(450, (int) $donor->fresh()->pp);
        $this->assertSame(1, CourseDonate::where('idempotency_key', 'idem-key-1')->count());
    }

    public function test_owner_can_point_donate_to_own_course(): void
    {
        $owner = User::factory()->create(['pp' => 500]);
        $course = $this->makeCourse($owner);
        $service = app(CourseDonateService::class);

        $service->createPointDonation($owner, $course, 100, [], null);

        $this->assertSame(400, (int) $owner->fresh()->pp);
        $this->assertSame(100, (int) CoursePointAccount::where('course_id', $course->id)->first()->balance);
    }

    public function test_owner_can_cash_donate_to_own_course(): void
    {
        $owner = User::factory()->create();
        $course = $this->makeCourse($owner);
        $service = app(CourseDonateService::class);

        $donation = $service->createCashDonation($owner, $course, 200.00, ['payment_method' => 'bank_transfer'], null, null);

        $this->assertSame(CourseDonate::STATUS_PENDING, $donation->status);
        $this->assertNull(CoursePointAccount::where('course_id', $course->id)->first());
    }

    public function test_insufficient_pp_returns_error(): void
    {
        $owner = User::factory()->create();
        $donor = User::factory()->create(['pp' => 10]);
        $course = $this->makeCourse($owner);
        $service = app(CourseDonateService::class);

        $this->expectException(\DomainException::class);
        $service->createPointDonation($donor, $course, 100, [], null);
    }

    public function test_cash_donation_endpoint_accepts_anonymous_sent_as_multipart_string(): void
    {
        $owner = User::factory()->create();
        $donor = User::factory()->create(['pp' => 500]);
        $course = Course::factory()->create(['user_id' => $owner->id, 'donation_enabled' => true, 'status' => 1]);
        Storage::fake('local');

        // Multipart form data serializes booleans as the strings "true"/"false".
        $response = $this->actingAs($donor, 'api')->post("/api/courses/{$course->id}/donations/cash", [
            'cash_amount' => 100,
            'payment_method' => 'bank_transfer',
            'anonymous' => 'true',
            'slip' => UploadedFile::fake()->image('slip.jpg'),
        ]);

        $response->assertSuccessful()->assertJsonPath('data.anonymous', true);
    }

    public function test_cash_donation_creates_pending_row_and_does_not_credit_account(): void
    {
        $owner = User::factory()->create();
        $donor = User::factory()->create();
        $course = $this->makeCourse($owner);
        $service = app(CourseDonateService::class);

        $donation = $service->createCashDonation($donor, $course, 200.00, ['payment_method' => 'bank_transfer'], null, null);

        $this->assertSame(CourseDonate::STATUS_PENDING, $donation->status);
        $this->assertNull(CoursePointAccount::where('course_id', $course->id)->first());
    }

    public function test_admin_approve_credits_account(): void
    {
        $owner = User::factory()->create();
        $donor = User::factory()->create();
        $admin = User::factory()->create();
        $course = $this->makeCourse($owner);
        $service = app(CourseDonateService::class);
        $donation = $service->createCashDonation($donor, $course, 200.00, ['payment_method' => 'bank_transfer'], null, null);

        $approved = $service->approve($donation, $admin, 'verified slip');

        $this->assertSame(CourseDonate::STATUS_COMPLETED, $approved->status);
        $this->assertSame((int) round(200 * config('economy.donation_pp_per_baht')), (int) CoursePointAccount::where('course_id', $course->id)->first()->balance);
        $this->assertNotNull($approved->course_point_transaction_id);
        $this->assertSame(1, CoursePointTransaction::where('type', 'donation_cash_credit')->count());
    }

    public function test_admin_reject_does_not_credit_account(): void
    {
        $owner = User::factory()->create();
        $donor = User::factory()->create();
        $admin = User::factory()->create();
        $course = $this->makeCourse($owner);
        $service = app(CourseDonateService::class);
        $donation = $service->createCashDonation($donor, $course, 200.00, ['payment_method' => 'bank_transfer'], null, null);

        $rejected = $service->reject($donation, $admin, 'fake slip');

        $this->assertSame(CourseDonate::STATUS_REJECTED, $rejected->status);
        $this->assertSame('fake slip', $rejected->rejection_reason);
        $this->assertNull(CoursePointAccount::where('course_id', $course->id)->first());
    }

    public function test_admin_cannot_approve_donation_to_own_course(): void
    {
        $owner = User::factory()->create();
        $donor = User::factory()->create();
        $course = $this->makeCourse($owner);
        $service = app(CourseDonateService::class);
        $donation = $service->createCashDonation($donor, $course, 200.00, ['payment_method' => 'bank_transfer'], null, null);

        $this->expectException(\DomainException::class);
        $service->approve($donation, $owner, 'self approve');
    }
}
