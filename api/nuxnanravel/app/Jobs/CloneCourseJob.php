<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Course;
use App\Models\Notification;
use App\Models\PointsTransaction;
use App\Models\WalletTransaction;
use App\Services\CourseCloneService;
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

    protected $source;
    protected $newOwner;
    protected $purchaseTransactionId;
    protected $paymentMode;

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
    public function __construct(Course $source, User $newOwner, ?int $purchaseTransactionId = null, ?string $paymentMode = 'auto')
    {
        $this->source = $source;
        $this->newOwner = $newOwner;
        $this->purchaseTransactionId = $purchaseTransactionId;
        $this->paymentMode = $paymentMode;
    }

    /**
     * Execute the job.
     */
    public function handle(CourseCloneService $cloneService): void
    {
        try {
            $newCourse = $cloneService->clone($this->source, $this->newOwner);
            
            // Dispatch notification to newOwner
            Notification::create([
                'user_id' => $this->newOwner->id,
                'type' => Notification::TYPE_COURSE,
                'title' => 'คัดลอกวิชาสำเร็จ',
                'message' => "วิชา \"{$this->source->name}\" ถูกเพิ่มในคลังของคุณแล้ว คุณสามารถเริ่มจัดการเนื้อหาได้ทันที",
                'metadata' => [
                    'course_id' => $newCourse->id,
                    'source_course_id' => $this->source->id,
                    'action_url' => "/Learn/Courses/{$newCourse->id}/settings"
                ],
                'read_status' => false
            ]);
            
            Log::info("Course cloned successfully: {$newCourse->id} from {$this->source->id} for user {$this->newOwner->id}");
        } catch (\Exception $e) {
            Log::error("Failed to clone course {$this->source->id} for user {$this->newOwner->id}: " . $e->getMessage());
            
            // Handle refund if purchaseTransactionId is provided
            if ($this->purchaseTransactionId) {
                $this->handleRefund();
            }

            // Notify user about failure
            Notification::create([
                'user_id' => $this->newOwner->id,
                'type' => Notification::TYPE_GENERAL,
                'title' => 'ไม่สามารถคัดลอกวิชาได้',
                'message' => "เกิดข้อผิดพลาดในการคัดลอกวิชา \"{$this->source->name}\" ระบบได้ทำการคืนเงิน/แต้มให้คุณแล้ว กรุณาลองใหม่อีกครั้ง",
                'metadata' => [
                    'source_course_id' => $this->source->id,
                    'error' => $e->getMessage()
                ],
                'read_status' => false
            ]);

            throw $e;
        }
    }

    protected function handleRefund(): void
    {
        DB::transaction(function () {
            if ($this->paymentMode === 'points') {
                $pointsTransaction = PointsTransaction::find($this->purchaseTransactionId);
                if ($pointsTransaction && $pointsTransaction->transaction_type === 'spend') {
                    $pointsService = app(PointsService::class);
                    $pointsService->refund(
                        $this->newOwner, 
                        $pointsTransaction->amount, 
                        'App\Models\Course', 
                        $this->source->id, 
                        "Refund for failed course clone: {$this->source->name}"
                    );
                    
                    // Also deduct from seller if possible (might be tricky if they already spent it)
                    // For simplicity, we might just assume the platform covers the loss or handle seller deduction manually if needed.
                    // But to be consistent, we should try to find the seller's 'earn' transaction and reverse it.
                    $sellerTransaction = PointsTransaction::where('source_type', 'App\Models\Course')
                        ->where('source_id', $this->source->id)
                        ->where('transaction_type', 'earn')
                        ->where('amount', $pointsTransaction->amount)
                        ->where('created_at', '>=', $pointsTransaction->created_at)
                        ->latest()
                        ->first();
                    
                    if ($sellerTransaction) {
                        $seller = $sellerTransaction->user;
                        $seller->decrement('pp', $sellerTransaction->amount);
                        $sellerTransaction->update(['status' => 'failed', 'description' => $sellerTransaction->description . " (Refunded due to clone failure)"]);
                    }
                }
            } elseif ($this->paymentMode === 'wallet') {
                $walletTransaction = WalletTransaction::find($this->purchaseTransactionId);
                if ($walletTransaction && $walletTransaction->transaction_type === 'purchase') {
                    $walletService = app(WalletService::class);
                    $walletService->refundCoursePurchase(
                        $this->newOwner, 
                        $this->source, 
                        $walletTransaction->amount, 
                        "System Refund: Clone failure"
                    );

                    // Reverse seller income
                    $incomeTransaction = WalletTransaction::where('transaction_type', 'course_income')
                        ->whereJsonContains('metadata->course_id', $this->source->id)
                        ->whereJsonContains('metadata->buyer_id', $this->newOwner->id)
                        ->where('status', 'completed')
                        ->latest()
                        ->first();

                    if ($incomeTransaction) {
                        $seller = $incomeTransaction->user;
                        $seller->decrement('wallet', $incomeTransaction->amount);
                        $incomeTransaction->update(['status' => 'failed', 'description' => $incomeTransaction->description . " (Refunded due to clone failure)"]);
                    }
                }
            }
        });
    }
}
