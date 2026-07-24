<?php

namespace App\Services\Campaign;

use App\Enums\CampaignPaymentStatus;
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
}
