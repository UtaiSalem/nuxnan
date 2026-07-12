<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class DailyViewLimitException extends RuntimeException
{
    public function __construct(string $message = 'ผู้ใช้ดูโฆษณานี้ครบโควตารายวันแล้ว')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 429);
    }
}
