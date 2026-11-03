<?php

namespace App\Modules\Booking\Validations\Rules;

use App\Modules\Booking\Exceptions\BookingValidationException;
use App\Modules\Session\Enums\SessionStatus;
use App\Modules\Session\Models\TrainingSession;

final class EnsureSessionIsAvailable
{
    public function validate(TrainingSession $session): void
    {
        if (
            $session->status !== SessionStatus::Scheduled
            || $session->cancelled_at !== null
        ) {
            throw BookingValidationException::sessionNotAvailable();
        }

        if ($session->starts_at->lessThanOrEqualTo(now())) {
            throw BookingValidationException::sessionAlreadyStarted();
        }
    }
}