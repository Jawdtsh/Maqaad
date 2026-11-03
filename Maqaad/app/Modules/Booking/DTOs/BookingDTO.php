<?php

namespace App\Modules\Booking\DTOs;

final readonly class BookingDTO
{
    public function __construct(
        public int $sessionId,
        public int $studentId,
        public string $idempotencyKey,
        public string $requestFingerprint,
    ) {}
}