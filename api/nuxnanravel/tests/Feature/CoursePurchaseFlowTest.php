<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\WalletTransaction;
use App\Models\PointsTransaction;
use App\Models\Notification;
use App\Jobs\CloneCourseJob;
use App\Services\CoursePurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoursePurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ─────────────────────────────────────────────
    // Happy-path purchase
    // ─────────────────────────────────────────────

    public function test_purchase_course_successfully_sync()
    {
        $seller = User::factory()->create(['wallet' => 0]);
        $buyer  = User::factory()->create(['wallet' => 1000]);

        $course = Course::factory()->create([
            'user_id'           => $seller->id,
            'price'             => 500,
            'is_for_marketplace' => true,
            'saleable'          => true,
        ]);

        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), [
                'payment_mode' => 'wallet',
            ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('course_purchases', [
            'source_course_id' => $course->id,
            'buyer_id'         => $buyer->id,
            'status'           => 'completed',
            'amount_wallet'    => 500,
            'payment_mode'     => 'wallet',
        ]);

        $purchase = CoursePurchase::first();
        $this->assertNotNull($purchase->cloned_course_id);
        $this->assertNotNull($purchase->wallet_transaction_id);
        $this->assertNotNull($purchase->seller_income_transaction_id);

        $this->assertEquals(500, $buyer->fresh()->wallet);
        $this->assertEquals(500, $seller->fresh()->wallet);
    }

    public function test_purchase_course_large_dispatches_job()
    {
        Queue::fake();

        $seller = User::factory()->create();
        $buyer  = User::factory()->create(['wallet' => 1000]);

        $course = Course::factory()->create([
            'user_id'           => $seller->id,
            'price'             => 500,
            'is_for_marketplace' => true,
            'instructor_id'     => $seller->id,
        ]);

        \App\Models\Lesson::factory()->count(21)->create([
            'course_id' => $course->id,
            'user_id'   => $seller->id,
        ]);

        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), [
                'payment_mode' => 'wallet',
            ]);

        $response->assertStatus(200)->assertJsonPath('is_queued', true);

        $this->assertDatabaseHas('course_purchases', [
            'source_course_id' => $course->id,
            'status'           => 'pending_clone',
        ]);
    }

    // ─────────────────────────────────────────────
    // Retry / refund lifecycle (NEW behaviour)
    // ─────────────────────────────────────────────

    /**
     * handle() on failure must NOT refund — it should only throw so Laravel can retry.
     */
    public function test_handle_failure_throws_but_does_not_refund()
    {
        [$buyer, $seller, $purchase, $tx, $incomeTx] = $this->makePendingClonePurchase(500);

        $mockCloneService = \Mockery::mock(\App\Services\CourseCloneService::class);
        $mockCloneService->shouldReceive('clone')->andThrow(new \Exception('Disk full'));

        $job = new CloneCourseJob($purchase->id);

        $this->expectException(\Exception::class);
        $job->handle($mockCloneService);

        // Purchase must still be pending_clone — no refund yet
        $this->assertEquals('pending_clone', $purchase->fresh()->status);
        $this->assertNull($purchase->fresh()->refunded_at);

        // Balances unchanged
        $this->assertEquals(500, $buyer->fresh()->wallet);   // was decremented to 500 during setup
        $this->assertEquals(500, $seller->fresh()->wallet);  // was incremented to 500 during setup
    }

    /**
     * failed() — called by Laravel after all retries — must refund buyer and reverse seller income.
     */
    public function test_failed_method_refunds_after_all_retries_exhausted()
    {
        [$buyer, $seller, $purchase, $tx, $incomeTx] = $this->makePendingClonePurchase(500);

        $job = new CloneCourseJob($purchase->id);
        $job->failed(new \Exception('Persistent disk failure'));

        // Buyer refunded
        $this->assertEquals(1000, $buyer->fresh()->wallet);

        // Seller income reversed
        $this->assertEquals(0, $seller->fresh()->wallet);
        $this->assertEquals('failed', $incomeTx->fresh()->status);

        // Purchase marked refunded
        $purchase->refresh();
        $this->assertEquals('refunded', $purchase->status);
        $this->assertNotNull($purchase->failed_at);
        $this->assertNotNull($purchase->refunded_at);
    }

    /**
     * failed() must send a failure notification to the buyer.
     */
    public function test_failed_method_notifies_buyer()
    {
        [$buyer, $seller, $purchase] = $this->makePendingClonePurchase(500);

        $job = new CloneCourseJob($purchase->id);
        $job->failed(new \Exception('Clone error'));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->id,
            'type'    => Notification::TYPE_GENERAL,
        ]);

        $notification = Notification::where('user_id', $buyer->id)->first();
        $this->assertStringContainsString('ไม่สามารถคัดลอกวิชาได้', $notification->title);
    }

    /**
     * failed() must NOT double-refund if called again when already refunded.
     */
    public function test_failed_method_is_idempotent()
    {
        [$buyer, $seller, $purchase] = $this->makePendingClonePurchase(500);

        $job = new CloneCourseJob($purchase->id);
        $job->failed(new \Exception('First failure'));
        $job->failed(new \Exception('Second failure'));  // second call should be a no-op

        // Wallet should be restored exactly once (1000), not over-refunded
        $this->assertEquals(1000, $buyer->fresh()->wallet);
        $this->assertEquals(0, $seller->fresh()->wallet);
    }

    /**
     * failed() must decrement total_sales to undo the increment from CoursePurchaseService.
     */
    public function test_failed_method_decrements_total_sales()
    {
        [$buyer, $seller, $purchase] = $this->makePendingClonePurchase(500);

        $course = $purchase->sourceCourse;
        $course->update(['total_sales' => 1]);  // simulate the increment from purchase service

        $job = new CloneCourseJob($purchase->id);
        $job->failed(new \Exception('Clone error'));

        $this->assertEquals(0, $course->fresh()->total_sales);
    }

    /**
     * While retrying, handle() on a pending_clone purchase is NOT skipped.
     * Only completed/refunded/failed purchases are skipped.
     */
    public function test_handle_retries_pending_clone_purchase()
    {
        [$buyer, $seller, $purchase] = $this->makePendingClonePurchase(500);

        $mockCloneService = \Mockery::mock(\App\Services\CourseCloneService::class);
        // Second attempt succeeds
        $mockCloneService->shouldReceive('clone')->once()->andReturn(
            Course::factory()->create(['user_id' => $buyer->id])
        );

        $job = new CloneCourseJob($purchase->id);
        $job->handle($mockCloneService);

        $this->assertEquals('completed', $purchase->fresh()->status);
    }

    /**
     * handle() must skip purchases already completed (idempotency guard).
     */
    public function test_handle_skips_already_completed_purchase()
    {
        [$buyer, $seller, $purchase] = $this->makePendingClonePurchase(500);
        $purchase->update(['status' => 'completed']);

        $mockCloneService = \Mockery::mock(\App\Services\CourseCloneService::class);
        $mockCloneService->shouldNotReceive('clone');

        $job = new CloneCourseJob($purchase->id);
        $job->handle($mockCloneService);  // must not throw
    }

    // ─────────────────────────────────────────────
    // Guard rails
    // ─────────────────────────────────────────────

    public function test_self_purchase_is_rejected()
    {
        $owner = User::factory()->create(['wallet' => 1000]);
        $course = Course::factory()->create([
            'user_id'           => $owner->id,
            'price'             => 100,
            'is_for_marketplace' => true,
        ]);

        $response = $this->actingAs($owner, 'api')
            ->postJson(route('courses.purchase', $course->id), ['payment_mode' => 'wallet']);

        $response->assertStatus(400);
        $this->assertStringContainsString('own', strtolower($response->json('message')));
    }

    public function test_duplicate_purchase_is_rejected()
    {
        $seller = User::factory()->create(['wallet' => 0]);
        $buyer  = User::factory()->create(['wallet' => 2000]);
        $course = Course::factory()->create([
            'user_id'           => $seller->id,
            'price'             => 100,
            'is_for_marketplace' => true,
        ]);

        // First purchase
        $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), ['payment_mode' => 'wallet'])
            ->assertStatus(200);

        // Second purchase should be rejected
        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), ['payment_mode' => 'wallet']);

        $response->assertStatus(400);
        $this->assertStringContainsString('already', strtolower($response->json('message')));
    }

    public function test_marketplace_price_cannot_be_negative()
    {
        $owner = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner, 'api')
            ->patchJson(route('course.marketplace.update', $course->id), [
                'is_for_marketplace' => true,
                'price_type'         => 'wallet',
                'price'              => -50,
            ]);

        $response->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    /**
     * Create a purchase in pending_clone state with wallet transactions already applied.
     * Returns [$buyer, $seller, $purchase, $buyerTx, $sellerIncomeTx].
     */
    private function makePendingClonePurchase(float $amount): array
    {
        $seller = User::factory()->create(['wallet' => 0]);
        $buyer  = User::factory()->create(['wallet' => 1000, 'pp' => 0]);

        $course = Course::factory()->create([
            'user_id'           => $seller->id,
            'price'             => $amount,
            'is_for_marketplace' => true,
        ]);

        $purchase = CoursePurchase::create([
            'source_course_id' => $course->id,
            'buyer_id'         => $buyer->id,
            'seller_id'        => $seller->id,
            'amount_wallet'    => $amount,
            'payment_mode'     => 'wallet',
            'status'           => 'paid',
        ]);

        $buyerTx = WalletTransaction::create([
            'user_id'          => $buyer->id,
            'transaction_type' => 'purchase',
            'amount'           => $amount,
            'balance_before'   => 1000,
            'balance_after'    => 1000 - $amount,
            'status'           => 'completed',
            'metadata'         => ['course_id' => $course->id],
        ]);
        $buyer->decrement('wallet', $amount);

        $sellerTx = WalletTransaction::create([
            'user_id'          => $seller->id,
            'transaction_type' => 'course_income',
            'amount'           => $amount,
            'balance_before'   => 0,
            'balance_after'    => $amount,
            'status'           => 'completed',
            'metadata'         => ['course_id' => $course->id],
        ]);
        $seller->increment('wallet', $amount);

        $purchase->update([
            'wallet_transaction_id'        => $buyerTx->id,
            'seller_income_transaction_id' => $sellerTx->id,
            'status'                       => 'pending_clone',
        ]);

        return [$buyer, $seller, $purchase, $buyerTx, $sellerTx];
    }
}
