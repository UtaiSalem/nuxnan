<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Withdrawal settings
    |--------------------------------------------------------------------------
    |
    | Single source of truth for withdrawal rules. The frontend mirrors these
    | values in ui/composables/useWallet.ts — keep them in sync when changing.
    |
    | fee is calculated as: max(amount * fee_rate, fee_min), rounded to 2 dp.
    | To raise revenue later, just bump fee_rate / fee_min here.
    |
    */
    'withdraw' => [
        // Minimum amount a user may withdraw (THB).
        'min_amount' => (float) env('WALLET_WITHDRAW_MIN_AMOUNT', 25),

        // Percentage fee on real withdrawals (0.005 = 0.5%).
        'fee_rate' => (float) env('WALLET_WITHDRAW_FEE_RATE', 0.005),

        // Minimum fee floor in THB — applied when the percentage is smaller.
        'fee_min' => (float) env('WALLET_WITHDRAW_FEE_MIN', 5),
    ],

];
