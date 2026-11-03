<?php

namespace App\Modules\Booking\Exceptions;

use DomainException;

final class BookingValidationException extends DomainException
{
    private function __construct(
        string $message,
        public readonly int $httpStatus
    ) {
        parent::__construct($message);
    }

    public static function sessionNotAvailable(): self
    {
        return new self(
            'This training session is not available for booking.',
            422
        );
    }

    public static function sessionAlreadyStarted(): self
    {
        return new self(
            'Cannot book a session that has already started.',
            422
        );
    }

    public static function studentAlreadyBooked(): self
    {
        return new self(
            'The student already has an active booking for this session.',
            409
        );
    }

    public static function overlappingBooking(): self
    {
        return new self(
            'The student has an overlapping confirmed booking.',
            409
        );
    }

    public static function insufficientBalance(): self
    {
        return new self(
            'Insufficient wallet balance.',
            402
        );
    }

    public static function idempotencyKeyConflict(): self
    {
        return new self(
            'The idempotency key was used for a different request.',
            409
        );
    }
}
