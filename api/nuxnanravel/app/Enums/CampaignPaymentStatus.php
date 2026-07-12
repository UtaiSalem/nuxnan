<?php

namespace App\Enums;

enum CampaignPaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PENDING_SLIP = 'pending_slip';
    case PAID = 'paid';
    case REFUNDED = 'refunded';
}
