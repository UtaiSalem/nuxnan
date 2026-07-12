<?php

namespace App\Enums;

enum CampaignScopeType: string
{
    case PUBLIC = 'public';
    case ACADEMY = 'academy';
    case COURSE = 'course';
}
