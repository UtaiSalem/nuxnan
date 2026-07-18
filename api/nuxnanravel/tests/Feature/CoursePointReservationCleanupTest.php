<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\CoursePointCampaign;
use App\Models\CoursePointTransaction;
use App\Models\User;
use App\Services\CoursePointAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CoursePointReservationCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_command_closes_expired_campaigns_and_releases_reserve(): void
    {
        $course = Course::factory()->create();
        $user = User::factory()->create();
        $account = CoursePointAccount::create(['course_id' => $course->id, 'balance' => 1000, 'reserved_balance' => 0]);
        $campaign = DB::transaction(fn () => app(CoursePointAccountService::class)->createCampaign($course->id, [
            'title' => 'Expired campaign', 'points_per_claim' => 50, 'max_claims' => 10, 'ends_at' => now()->subMinute(),
        ], $user->id)['campaign']);

        $this->assertSame(500, $account->fresh()->reserved_balance);
        Artisan::call('course-points:cleanup-reservations');

        $this->assertSame(0, $account->fresh()->reserved_balance);
        $this->assertSame(CoursePointCampaign::STATUS_ENDED, $campaign->fresh()->status);
        $this->assertSame(1, CoursePointTransaction::where('type', CoursePointTransaction::TYPE_CAMPAIGN_RELEASE)->where('amount', 500)->count());
    }
}
