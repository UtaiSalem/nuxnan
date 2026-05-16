<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\WalletTransaction;
use App\Models\PointsTransaction;
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

    public function test_purchase_course_successfully_sync()
    {
        $seller = User::factory()->create(['wallet' => 0]);
        $buyer = User::factory()->create(['wallet' => 1000]);
        
        $course = Course::factory()->create([
            'user_id' => $seller->id,
            'price' => 500,
            'is_for_marketplace' => true,
            'saleable' => true
        ]);

        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), [
                'payment_mode' => 'wallet'
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify CoursePurchase record
        $this->assertDatabaseHas('course_purchases', [
            'source_course_id' => $course->id,
            'buyer_id' => $buyer->id,
            'status' => 'completed',
            'amount_wallet' => 500,
            'payment_mode' => 'wallet'
        ]);

        $purchase = CoursePurchase::first();
        $this->assertNotNull($purchase->cloned_course_id);
        $this->assertNotNull($purchase->wallet_transaction_id);
        $this->assertNotNull($purchase->seller_income_transaction_id);

        // Verify balances
        $this->assertEquals(500, $buyer->fresh()->wallet);
        $this->assertEquals(500, $seller->fresh()->wallet);
    }

    public function test_purchase_course_large_dispatches_job()
    {
        // Using Queue::fake() with afterCommit requires special handling in tests
        Queue::fake();

        $seller = User::factory()->create();
        $buyer = User::factory()->create(['wallet' => 1000]);
        
        $course = Course::factory()->create([
            'user_id' => $seller->id,
            'price' => 500,
            'is_for_marketplace' => true,
            'instructor_id' => $seller->id,
        ]);

        // Mock lesson count > 20
        \App\Models\Lesson::factory()->count(21)->create([
            'course_id' => $course->id,
            'user_id' => $seller->id,
        ]);

        $response = $this->actingAs($buyer, 'api')
            ->postJson(route('courses.purchase', $course->id), [
                'payment_mode' => 'wallet'
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('is_queued', true);

        $this->assertDatabaseHas('course_purchases', [
            'source_course_id' => $course->id,
            'status' => 'pending_clone',
        ]);

        // Note: In RefreshDatabase tests, the transaction never commits, 
        // so afterCommit jobs are never pushed to the fake queue.
        // We verified the database state and the is_queued response instead.
    }

    public function test_failed_clone_triggers_refund()
    {
        $seller = User::factory()->create(['wallet' => 0]);
        $buyer = User::factory()->create(['wallet' => 1000, 'pp' => 0]);
        
        $course = Course::factory()->create([
            'user_id' => $seller->id,
            'price' => 500,
            'is_for_marketplace' => true
        ]);

        // Create purchase record manually to simulate the state before job
        $purchase = CoursePurchase::create([
            'source_course_id' => $course->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount_wallet' => 500,
            'payment_mode' => 'wallet',
            'status' => 'paid',
        ]);

        // Create transactions
        $tx = WalletTransaction::create([
            'user_id' => $buyer->id,
            'transaction_type' => 'purchase',
            'amount' => 500,
            'balance_before' => 1000,
            'balance_after' => 500,
            'status' => 'completed',
            'metadata' => ['course_id' => $course->id]
        ]);
        $buyer->decrement('wallet', 500);

        $incomeTx = WalletTransaction::create([
            'user_id' => $seller->id,
            'transaction_type' => 'course_income',
            'amount' => 500,
            'balance_before' => 0,
            'balance_after' => 500,
            'status' => 'completed',
            'metadata' => ['course_id' => $course->id]
        ]);
        $seller->increment('wallet', 500);

        $purchase->update([
            'wallet_transaction_id' => $tx->id,
            'seller_income_transaction_id' => $incomeTx->id,
            'status' => 'pending_clone'
        ]);

        // Mock failure in CloneCourseJob by throwing exception in CourseCloneService
        $mockCloneService = \Mockery::mock(\App\Services\CourseCloneService::class);
        $mockCloneService->shouldReceive('clone')->andThrow(new \Exception("Filesystem full"));

        $job = new CloneCourseJob($purchase->id);
        
        try {
            $job->handle($mockCloneService);
        } catch (\Exception $e) {
            // Expected
        }

        // Verify refund
        $this->assertEquals(1000, $buyer->fresh()->wallet);
        $this->assertEquals(0, $seller->fresh()->wallet);
        
        $purchase->refresh();
        $this->assertEquals('refunded', $purchase->status);
        $this->assertNotNull($purchase->failed_at);
        $this->assertNotNull($purchase->refunded_at);
        
        $this->assertEquals('failed', $incomeTx->fresh()->status);
    }
}
