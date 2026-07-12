<?php

namespace App\Services\Campaign;

use App\Enums\CampaignPaymentStatus;
use App\Enums\CampaignScopeType;
use App\Enums\CampaignType;
use App\Models\Advert;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use RuntimeException;

class SupportPaymentService
{
    public function __construct(private DatabaseManager $db) {}

    public function payWithWallet(Advert $campaign, User $payer): Advert
    {
        return $this->db->transaction(function () use ($campaign, $payer) {
            $locked = Advert::whereKey($campaign->id)->lockForUpdate()->firstOrFail();
            $user = User::whereKey($payer->id)->lockForUpdate()->firstOrFail();
            $amount = (float) ($locked->budget_amount ?? $locked->amounts ?? 0);

            if ($amount <= 0 || (float) $user->wallet < $amount) {
                throw new RuntimeException('ยอดเงินใน Wallet ไม่เพียงพอ');
            }

            $user->decrement('wallet', $amount);
            $locked->forceFill([
                'payment_status' => CampaignPaymentStatus::PAID,
            ])->save();

            return $locked->refresh();
        });
    }

    public function markSlipPending(Advert $campaign): Advert
    {
        $campaign->forceFill(['payment_status' => CampaignPaymentStatus::PENDING_SLIP])->save();

        return $campaign->refresh();
    }

    public function distributeSupport(Advert $campaign): void
    {
        $this->db->transaction(function () use ($campaign) {
            $locked = Advert::whereKey($campaign->id)->lockForUpdate()->firstOrFail();

            // Only paid support campaigns are distributable.
            if ($locked->campaign_type?->value !== CampaignType::SUPPORT->value) {
                return;
            }
            if ($locked->payment_status?->value !== CampaignPaymentStatus::PAID->value) {
                return;
            }

            // Idempotency guard: never distribute the same campaign twice, even on re-approve.
            if ($locked->distributed_at !== null) {
                return;
            }

            $total = (float) $locked->budget_amount;
            if ($total <= 0) {
                return;
            }

            // Reward the supporter with platform points in exchange for their contribution.
            if ((int) $locked->exchange_points > 0) {
                $supporter = User::whereKey($locked->user_id)->lockForUpdate()->first();
                if ($supporter) {
                    $supporter->increment('pp', (int) $locked->exchange_points);
                }
            }

            $splitConfig = config('campaign.revenue_split', [
                'academy' => 0.70,
                'instructor' => 0.20,
                'platform' => 0.10,
            ]);

            $academyShare = 0.0;
            $instructorShare = 0.0;
            $platformShare = 0.0;

            $academyOwner = null;
            $instructor = null;

            // Find platform user
            $platformUser = User::where('personal_code', config('campaign.platform_account_code', '99999999'))->first()
                ?? User::where('id', 1)->first()
                ?? User::whereHas('roles', function ($q) {
                    $q->where('name', 'SUPER_ADMIN');
                })->first();

            if ($locked->scope_type?->value === CampaignScopeType::COURSE->value) {
                $course = $locked->course;
                if ($course) {
                    $instructorId = $course->instructor_id ?? $course->user_id ?? $course->creator_id;
                    if ($instructorId) {
                        $instructor = User::find($instructorId);
                    }
                    $academy = $course->academy ?? $locked->academy;
                    if ($academy) {
                        $academyOwner = User::find($academy->user_id);
                    }
                }
            } elseif ($locked->scope_type?->value === CampaignScopeType::ACADEMY->value) {
                $academy = $locked->academy;
                if ($academy) {
                    $academyOwner = User::find($academy->user_id);
                }
            }

            // Calculate split
            if ($academyOwner && $instructor) {
                $academyShare = $total * $splitConfig['academy'];
                $instructorShare = $total * $splitConfig['instructor'];
                $platformShare = $total * $splitConfig['platform'];
            } elseif ($academyOwner) {
                $academyShare = $total * ($splitConfig['academy'] + $splitConfig['instructor']);
                $platformShare = $total * $splitConfig['platform'];
            } elseif ($instructor) {
                $instructorShare = $total * ($splitConfig['academy'] + $splitConfig['instructor']);
                $platformShare = $total * $splitConfig['platform'];
            } else {
                $platformShare = $total;
            }

            // Distribute wallet funds
            if ($academyShare > 0 && $academyOwner) {
                $academyOwner->increment('wallet', $academyShare);
            }
            if ($instructorShare > 0 && $instructor) {
                $instructor->increment('wallet', $instructorShare);
            }
            if ($platformShare > 0 && $platformUser) {
                $platformUser->increment('wallet', $platformShare);
            }

            $locked->forceFill(['distributed_at' => now()])->save();
        });
    }
}
