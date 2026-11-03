<?php

namespace App\Modules\Booking\Validations\Rules;

use App\Modules\Booking\Exceptions\BookingValidationException;
use App\Modules\Booking\Queries\BookingQuery;

final class EnsureStudentNotAlreadyBooked
{
    public function __construct(
        private readonly BookingQuery $bookingQuery
    ) {}

    public function validate(int $sessionId,int $studentId): void 
    {
        if ($this->bookingQuery->hasActiveBooking($studentId,$sessionId)) 
            {
            throw BookingValidationException::studentAlreadyBooked();
        }
    }
}