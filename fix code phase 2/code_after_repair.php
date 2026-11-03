<?php

namespace App\Services;

use App\Jobs\SendBookingConfirmation;
use App\Models\Booking;
use App\Models\CourseSession;
use App\Models\Student;
use App\Models\Wallet;
use App\Triat\ValidatesBookingTriat;
use Illuminate\Support\Facades\DB;

class BookingService
{
    use ValidatesBookingTriat;

    public function bookSeat(
        int $studentId,
        int $sessionId,
        string $idempotencyKey,
        string $requestFingerprint
    ): Booking {
        return DB::transaction(function () use (
            $studentId,
            $sessionId,
            $idempotencyKey,
            $requestFingerprint
        ) {
            // Serialize requests using the same idempotency key.
            $previousBooking = $this->findPreviousBookingOrFailOnConflict(
                $studentId,
                $idempotencyKey,
                $requestFingerprint
            );

            if ($previousBooking !== null) {
                return $previousBooking;
            }

            // Lock the session before checking capacity.
            $session = CourseSession::query()
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureSessionIsBookable($session);

            // Serialize booking decisions for the same student.
            Student::query()
                ->whereKey($studentId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureStudentHasNoActiveOrOverlappingBooking(
                $studentId,
                $session
            );

            // Read the course price under a lock.
            $course = $session->course()
                ->lockForUpdate()
                ->firstOrFail();

            $price = $course->price;

            // COUNT does not require FOR UPDATE here.
            $taken = Booking::query()
                ->where('session_id', $sessionId)
                ->where('status', 'confirmed')
                ->count();

            $this->ensureSeatIsAvailable($taken, $session->capacity);

            // Lock the wallet before validating or changing its balance.
            $wallet = Wallet::query()
                ->where('student_id', $studentId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureSufficientBalance($wallet, $price);

            // Save the booking with immutable financial snapshots.
            $booking = $this->createConfirmedBooking(
                $studentId,
                $session,
                $price
            );

            // Record the charge and update the balance atomically.
            $this->chargeWalletAndRecordTransaction(
                $wallet,
                $booking,
                $price
            );

            // Persist the successful idempotency result.
            $this->storeIdempotencyResult(
                $studentId,
                $idempotencyKey,
                $requestFingerprint,
                $booking
            );

            // Queue the notification only after a successful commit.
            SendBookingConfirmation::dispatch($booking)->afterCommit();

            return $booking;

        }, attempts: 5);
    }
}