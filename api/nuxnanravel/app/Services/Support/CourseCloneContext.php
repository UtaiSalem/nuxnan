<?php

namespace App\Services\Support;

class CourseCloneContext
{
    public function __construct(
        public string $mode = 'self_duplicate', // self_duplicate or purchase
        public ?int $paymentTransactionId = null,
        public ?string $paymentMode = null,
        public bool $addCopySuffix = false,
        public bool $copyMarketplaceState = false,
        public ?int $sourceCourseId = null,
    ) {}

    public static function make(array $data): self
    {
        return new self(
            mode: $data['mode'] ?? 'self_duplicate',
            paymentTransactionId: $data['payment_transaction_id'] ?? null,
            paymentMode: $data['payment_mode'] ?? null,
            addCopySuffix: $data['add_copy_suffix'] ?? false,
            copyMarketplaceState: $data['copy_marketplace_state'] ?? false,
            sourceCourseId: $data['source_course_id'] ?? null,
        );
    }
}
