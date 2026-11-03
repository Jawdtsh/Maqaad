<?php

namespace App\Modules\Booking\Enums;

enum BookingStatus: string
{
    case Confirmed = 'confirmed';
    case Waitlisted = 'waitlisted';
    case CancelledByStudent = 'cancelled_by_student';
    case CancelledByCenter = 'cancelled_by_center';

    public function isActive(): bool
    {
        return in_array($this, [
            self::Confirmed,
            self::Waitlisted,
        ], true);
    }

    public function isCancelled(): bool
    {
        return in_array($this, [
            self::CancelledByStudent,
            self::CancelledByCenter,
        ], true);
    }
}