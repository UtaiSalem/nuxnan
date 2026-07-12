<?php

namespace App\Services\Campaign;

use App\Enums\CampaignPaymentStatus;
use App\Models\Advert;
use App\Models\User;
use Illuminate\Database\DatabaseManager;

class CampaignRefundService
{
    public function __construct(private DatabaseManager $db) {}

    public function refundWallet(Advert $campaign): Advert
    {
        return $this->db->transaction(function () use ($campaign) {
            $locked = Advert::whereKey($campaign->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_status?->value !== CampaignPaymentStatus::PAID->value || $locked->refunded_at) {
                return $locked;
            }

            $amount = (float) ($locked->budget_amount ?? $locked->amounts ?? 0);
            $owner = User::whereKey($locked->user_id)->lockForUpdate()->firstOrFail();
            $owner->increment('wallet', $amount);
            $locked->forceFill([
                'payment_status' => CampaignPaymentStatus::REFUNDED,
                'refunded_at' => now(),
            ])->save();

            return $locked->refresh();
        });
    }
}
