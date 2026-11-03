<?php

namespace App\Modules\Booking\Exceptions;

use RuntimeException;

final class BookingException extends RuntimeException
{
    public function __construct(string $message,public readonly int $httpStatus = 422)
    {
        parent::__construct($message);
    }
}