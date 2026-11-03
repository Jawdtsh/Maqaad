<?php

namespace App\Modules\Booking\Queries;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Session\Models\TrainingSession;

final class BookingQuery
{
    public function hasActiveBooking(int $studentId,int $sessionId): bool
    {
        return Booking::query()
            ->where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->whereIn('status', [
                BookingStatus::Confirmed->value,
                BookingStatus::Waitlisted->value,
            ])
            ->exists();
    }

    public function confirmedCount(int $sessionId): int
    {
        return Booking::query()
            ->where('session_id', $sessionId)
            ->where('status', BookingStatus::Confirmed->value)
            ->count();
    }

    public function hasAvailableSeat(TrainingSession $session): bool
    {
        return $this->confirmedCount($session->id) < $session->capacity;
    }

    public function hasOverlappingActiveBooking(
        int $studentId,
        TrainingSession $session
    ): bool {
        return Booking::query()
            ->join(
                'sessions',
                'sessions.id',
                '=',
                'bookings.session_id'
            )
            ->where('bookings.student_id', $studentId)
            ->whereIn('bookings.status', [
                BookingStatus::Confirmed->value,
                BookingStatus::Waitlisted->value,
            ])
            ->where(
                'sessions.starts_at',
                '<',
                $session->ends_at
            )
            ->where(
                'sessions.ends_at',
                '>',
                $session->starts_at
            )
            ->exists();
    }
}