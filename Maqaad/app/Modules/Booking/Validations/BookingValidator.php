<?php

namespace App\Modules\Booking\Validations;

use App\Modules\Booking\DTOs\BookingDTO;
use App\Modules\Booking\Validations\Rules\EnsureNoOverlappingBooking;
use App\Modules\Booking\Validations\Rules\EnsureSessionIsAvailable;
use App\Modules\Booking\Validations\Rules\EnsureStudentNotAlreadyBooked;
use App\Modules\Session\Models\TrainingSession;

final class BookingValidator
{
    public function __construct(
        private readonly EnsureSessionIsAvailable $sessionRule,
        private readonly EnsureStudentNotAlreadyBooked $duplicateRule,
        private readonly EnsureNoOverlappingBooking $overlapRule,
    ) {}

    public function validateRequest(TrainingSession $session,BookingDTO $dto): void
     {
        $this->sessionRule->validate($session);

        $this->duplicateRule->validate($session->id,$dto->studentId);
    }

    public function validateConfirmation(TrainingSession $session,BookingDTO $dto): void 
    {
        $this->overlapRule->validate($session,$dto->studentId);
    }
}