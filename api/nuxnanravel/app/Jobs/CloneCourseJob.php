<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\PointsTransaction;
use App\Models\WalletTransaction;
use App\Models\CoursePurchase;
use App\Services\CourseCloneService;
use App\Services\Support\CourseCloneContext;
use App\Services\PointsService;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CloneCourseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $purchaseId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // Increased timeout for large courses

    /**
     * Create a new job instance.
     */
    public function __construct(int $purchaseId)
    {
        $this->purchaseId = $purchaseId;
    }

    /**
     * Execute the job.
     */
    public function handle(CourseCloneService $cloneService): void
    {
        $purchase = CoursePurchase::with(['sourceCourse', 'buyer'])->find($this->purchaseId);
        
        if (!$purchase) {
            Log::error("CoursePurchase record not found: {$this->purchaseId}");
            return;
        }

        // Idempotency check
        if ($purchase->status === 'completed') {
            Log::info("Purchase {$this->purchaseId} already completed. Skipping.");
            return;
        }
        if (in_array($purchase->status, ['failed', 'refunded'])) {
            Log::info("Purchase {$this->purchaseId} is in status {$purchase->status}. Skipping.");
            return;
        }

        $source = $purchase->sourceCourse;
        $newOwner = $purchase->buyer;

        try {
            $context = new CourseCloneContext(mode: 'purchase');
            $newCourse = $cloneService->clone($source, $newOwner, $context);

            $purchase->update([
                'cloned_course_id' => $newCourse->id,
                'status' => 'completed',
                'cloned_at' => now(),
            ]);

            Notification::create([
                'user_id' => $newOwner->id,
                'type' => Notification::TYPE_COURSE,
                'title' => 'คัดลอกวิชาสำเร็จ',
                'message' => "วิชา \"{$source->name}\" ถูกเพิ่มในคลังของคุณแล้ว คุณสามารถเริ่มจัดการเนื้อหาได้ทันที",
                'metadata' => [
                    'course_id' => $newCourse->id,
                    'source_course_id' => $source->id,
                    'action_url' => "/Learn/Courses/{$newCourse->id}/settings"
                ],
                'read_status' => false
            ]);

            Log::info("Course cloned successfully: {$newCourse->id} from {$source->id} for user {$newOwner->id}");
        } catch (\Exception $e) {
            Log::error("Failed to clone course {$source->id} for user {$newOwner->id} (attempt {$this->attempts()}): " . $e->getMessage());
            // Let Laravel retry naturally — refund only happens in failed() after all retries are exhausted
            throw $e;
        }
    }

    /**
     * Called by Laravel after all retry attempts are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        $purchase = CoursePurchase::with(['sourceCourse', 'buyer'])->find($this->purchaseId);

        if (!$purchase) {
            Log::error("CloneCourseJob::failed — purchase {$this->purchaseId} not found");
            return;
        }

        Log::error("CloneCourseJob permanently failed for purchase {$this->purchaseId}: " . $exception->getMessage());

        $this->handleRefund($purchase);

        $source = $purchase->sourceCourse;
        $newOwner = $purchase->buyer;

        if ($newOwner && $source) {
            Notification::create([
                'user_id' => $newOwner->id,
                'type' => Notification::TYPE_GENERAL,
                'title' => 'ไม่สามารถคัดลอกวิชาได้',
                'message' => "เกิดข้อผิดพลาดในการคัดลอกวิชา \"{$source->name}\" ระบบได้ทำการคืนเงิน/แต้มให้คุณแล้ว กรุณาติดต่อผู้ดูแลระบบหากยังพบปัญหา",
                'metadata' => [
                    'source_course_id' => $source->id,
                    'error' => $exception->getMessage(),
                ],
                'read_status' => false,
            ]);
        }
    }

    protected function handleRefund(CoursePurchase $purchase): void
    {
        // Don't refund if already failed/refunded
        if (in_array($purchase->status, ['failed', 'refunded'])) {
            return;
        }

        DB::transaction(function () use ($purchase) {
            $walletService = app(WalletService::class);
            $pointsService = app(PointsService::class);

            // 1. Refund Buyer Wallet
            if ($purchase->wallet_transaction_id) {
                $walletTransaction = WalletTransaction::find($purchase->wallet_transaction_id);
                if ($walletTransaction && $walletTransaction->status === 'completed') {
                    $walletService->refundCoursePurchase(
                        $purchase->buyer, 
                        $purchase->sourceCourse, 
                        $purchase->amount_wallet, 
                        "System Refund: Clone failure"
                    );
                }
            }

            // 2. Refund Buyer Points
            if ($purchase->points_transaction_id) {
                $pointsTransaction = PointsTransaction::find($purchase->points_transaction_id);
                if ($pointsTransaction && $pointsTransaction->transaction_type === 'spend') {
                    $pointsService->refund(
                        $purchase->buyer, 
                        $purchase->amount_points, 
                        'App\Models\Course', 
                        $purchase->source_course_id, 
                        "Refund for failed course clone: {$purchase->sourceCourse->name}"
                    );
                }
            }

            // 3. Reverse Seller Income
            if ($purchase->seller_income_transaction_id) {
                $incomeTransaction = WalletTransaction::find($purchase->seller_income_transaction_id);
                if ($incomeTransaction && $incomeTransaction->status === 'completed') {
                    $seller = $incomeTransaction->user;
                    $seller->decrement('wallet', $incomeTransaction->amount);
                    $incomeTransaction->update([
                        'status' => 'failed', 
                        'description' => $incomeTransaction->description . " (Refunded due to clone failure)"
                    ]);
                }
            }

            $purchase->update([
                'status' => 'refunded',
                'failed_at' => now(),
                'refunded_at' => now(),
            ]);

            // Reverse the total_sales increment from CoursePurchaseService
            if ($purchase->sourceCourse) {
                $purchase->sourceCourse->decrement('total_sales');
            }
        });
    }
}
