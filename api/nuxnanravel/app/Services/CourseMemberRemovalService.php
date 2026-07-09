<?php

namespace App\Services;

use App\Models\AssignmentAnswer;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseGroupMember;
use App\Models\CourseMember;
use App\Models\CoursePurchase;
use App\Models\CourseQuizResult;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserAnswerQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CourseMemberRemovalService
{
    /**
     * Preview the consequences of removing a member.
     */
    public function preview(Course $course, CourseMember $member, User $actor): array
    {
        $isSelf = $actor->id === $member->user_id;

        // Find payment record
        $purchase = CoursePurchase::where('course_member_id', $member->id)
            ->where('purchase_type', 'enrollment')
            ->where('status', 'completed')
            ->first();

        if (! $purchase) {
            // Fallback for legacy data or marketplace purchases
            $purchase = CoursePurchase::where('buyer_id', $member->user_id)
                ->where('source_course_id', $course->id)
                ->whereIn('status', ['completed', 'paid'])
                ->first();
        }

        $canRefund = false;
        $refundAmount = 0;

        if ($purchase) {
            $refundAmount = $purchase->amount_wallet;
            // Admin remove or cancel request can refund
            // v1 policy: self-leave = no refund
            $canRefund = true;
        }

        // Count data to be deleted (Tier A & B)
        $quizResultsCount = CourseQuizResult::where('course_id', $course->id)->where('user_id', $member->user_id)->count();
        $answerCount = UserAnswerQuestion::where('course_id', $course->id)->where('user_id', $member->user_id)->count();

        $assignmentAnswerCount = $this->assignmentAnswersForCourseMember($course, $member)->count();

        return [
            'is_self' => $isSelf,
            'member_identity' => [
                'name' => $member->member_name,
                'code' => $member->member_code,
            ],
            'payment' => [
                'is_paid' => $purchase !== null,
                'purchase_id' => $purchase?->id,
                'amount' => $refundAmount,
                'can_refund' => $canRefund,
            ],
            'data_summary' => [
                'quiz_results' => $quizResultsCount,
                'question_answers' => $answerCount,
                'assignment_answers' => $assignmentAnswerCount,
            ],
            'warnings' => [
                'irreversible' => true,
                'self_leave_no_refund' => $isSelf && $purchase !== null,
                'admin_remove_refund' => ! $isSelf && $purchase !== null,
            ],
        ];
    }

    /**
     * Execute the removal.
     */
    public function execute(Course $course, CourseMember $member, User $actor, string $mode, ?string $reason = null): array
    {
        return DB::transaction(function () use ($course, $member, $actor, $mode, $reason) {
            $isSelf = $actor->id === $member->user_id;

            // 1. Authorization
            if ($mode === 'self_leave') {
                if (! $isSelf) {
                    throw new \Exception('Unauthorized mode for this actor');
                }
            } else {
                if (! $course->hasPermission($actor, 'remove_members')) {
                    throw new \Exception('Unauthorized');
                }
            }

            // 2. Refund logic (v1 policy)
            $refunded = false;
            $refundAmount = 0;

            if ($mode === 'admin_remove' || $mode === 'cancel_request') {
                $purchase = CoursePurchase::where('course_member_id', $member->id)
                    ->where('purchase_type', 'enrollment')
                    ->where('status', 'completed')
                    ->first();

                if (! $purchase) {
                    // Fallback for marketplace
                    $purchase = CoursePurchase::where('buyer_id', $member->user_id)
                        ->where('source_course_id', $course->id)
                        ->where('purchase_type', 'marketplace')
                        ->whereIn('status', ['completed', 'paid'])
                        ->first();
                }

                if ($purchase && $purchase->amount_wallet > 0) {
                    $walletService = app(WalletService::class);
                    $walletService->refundCoursePurchase($member->user, $course, $purchase->amount_wallet, $reason ?? 'Removed by admin');

                    $purchase->update([
                        'status' => 'refunded',
                        'refunded_at' => now(),
                    ]);

                    $refunded = true;
                    $refundAmount = $purchase->amount_wallet;
                }
            }

            // 3. Cleanup Tier A & B

            // Assignments cleanup (files)
            $assignmentAnswers = $this->assignmentAnswersForCourseMember($course, $member)->get();

            foreach ($assignmentAnswers as $answer) {
                if ($answer->images()->count() > 0) {
                    foreach ($answer->images as $image) {
                        Storage::disk('public')->delete('images/courses/assignments/answers/'.$image->filename);
                    }
                    $answer->images()->delete();
                }
                $answer->delete();
            }

            // Other cleanup
            UserAnswerQuestion::where('course_id', $course->id)->where('user_id', $member->user_id)->delete();
            CourseQuizResult::where('course_id', $course->id)->where('user_id', $member->user_id)->delete();

            // Delete ALL membership rows for this user in this course
            CourseGroupMember::where('course_id', $course->id)
                ->where('user_id', $member->user_id)
                ->delete();

            CourseMember::where('course_id', $course->id)
                ->where('user_id', $member->user_id)
                ->delete();

            // 4. Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'remove_member',
                'entity_type' => 'CourseMember',
                'entity_id' => $member->id,
                'module' => 'Learn',
                'old_values' => ['user_id' => $member->user_id, 'course_id' => $course->id],
                'new_values' => null,
                'metadata' => [
                    'mode' => $mode,
                    'reason' => $reason,
                    'refunded' => $refunded,
                    'refund_amount' => $refundAmount,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ]);

            // 5. Notifications
            if ($mode === 'self_leave') {
                Notification::create([
                    'user_id' => $actor->id,
                    'content' => "คุณออกจากรายวิชา {$course->name} แล้ว",
                    'type' => Notification::TYPE_COURSE_SELF_LEFT,
                    'sender_id' => $actor->id,
                    'related_id' => $course->id,
                ]);
            } else {
                Notification::create([
                    'user_id' => $member->user_id,
                    'content' => "คุณถูกนำออกจากรายวิชา {$course->name}".($reason ? " เนื่องจาก: {$reason}" : ''),
                    'type' => Notification::TYPE_COURSE_MEMBER_REMOVED,
                    'sender_id' => $actor->id,
                    'related_id' => $course->id,
                ]);

                if ($refunded) {
                    Notification::create([
                        'user_id' => $member->user_id,
                        'content' => "คุณได้รับเงินคืน {$refundAmount} บาท จากรายวิชา {$course->name}",
                        'type' => Notification::TYPE_COURSE_REMOVAL_REFUNDED,
                        'sender_id' => $actor->id,
                        'related_id' => $course->id,
                    ]);
                }
            }

            Log::info('Course member removed', [
                'course_id' => $course->id,
                'user_id' => $member->user_id,
                'actor_id' => $actor->id,
                'mode' => $mode,
                'refunded' => $refunded,
                'amount' => $refundAmount,
            ]);

            return [
                'success' => true,
                'refunded' => $refunded,
                'refund_amount' => $refundAmount,
            ];
        });
    }

    private function assignmentAnswersForCourseMember(Course $course, CourseMember $member): Builder
    {
        return AssignmentAnswer::query()
            ->where('user_id', $member->user_id)
            ->whereHas('assignment', function (Builder $query) use ($course) {
                $query->where(function (Builder $assignmentQuery) use ($course) {
                    $assignmentQuery
                        ->where(function (Builder $directCourseQuery) use ($course) {
                            $directCourseQuery
                                ->where('assignmentable_type', Course::class)
                                ->where('assignmentable_id', $course->id);
                        })
                        ->orWhere(function (Builder $lessonQuery) use ($course) {
                            $lessonQuery
                                ->where('assignmentable_type', Lesson::class)
                                ->whereIn('assignmentable_id', $course->courseLessons()->select('id'));
                        })
                        ->orWhere(function (Builder $topicQuery) use ($course) {
                            $topicQuery
                                ->where('assignmentable_type', Topic::class)
                                ->whereIn('assignmentable_id', Topic::query()
                                    ->where('course_id', $course->id)
                                    ->select('id'));
                        });
                });
            });
    }
}
