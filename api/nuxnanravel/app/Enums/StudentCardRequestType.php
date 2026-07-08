<?php

namespace App\Enums;

enum StudentCardRequestType: string
{
    case FirstIssue = 'first_issue';
    case Replacement = 'replacement';
    case Renewal = 'renewal';
}
