<?php

namespace App\Modules\Booking\Validations\Rules;

use App\Modules\Booking\Exceptions\BookingValidationException;
use App\Modules\Booking\Queries\BookingQuery;
use App\Modules\Session\Models\TrainingSession;

final class EnsureNoOverlappingBooking
{
    public function __construct(
        private readonly BookingQuery $bookingQuery
    ) {}

    public function validate(TrainingSession $session,int $studentId): void
    {
        if ($this->bookingQuery->hasOverlappingActiveBooking($studentId,$session))
        {
            throw BookingValidationException::overlappingBooking();
        }
    }
}