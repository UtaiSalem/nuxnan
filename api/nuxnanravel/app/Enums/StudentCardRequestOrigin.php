<?php

namespace App\Enums;

enum StudentCardRequestOrigin: string
{
    case Teacher = 'teacher';
    case Legacy = 'legacy';
    case Public = 'public';
}
