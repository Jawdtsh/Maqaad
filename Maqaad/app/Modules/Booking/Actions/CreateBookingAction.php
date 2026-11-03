<?php

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\DTOs\BookingDTO;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Queries\BookingQuery;
use App\Modules\Booking\Services\BookingConfirmationService;
use App\Modules\Booking\Services\IdempotencyService;
use App\Modules\Booking\Validations\BookingValidator;
use App\Modules\Session\Models\TrainingSession;
use App\Modules\Student\Models\Student;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Support\Facades\DB;

final class CreateBookingAction
{
    public function __construct(
        private readonly BookingValidator $validator,
        private readonly BookingQuery $bookingQuery,
        private readonly BookingConfirmationService $confirmationService,
        private readonly IdempotencyService $idempotencyService,
    ) {}

    public function execute(BookingDTO $dto): array
    {
        return DB::transaction(function () use ($dto): array {

            $idempotency = $this->idempotencyService->acquire($dto);

            $previousResult = $this->idempotencyService
                ->getPreviousResult($idempotency);

            if ($previousResult !== null) {
                return $previousResult;
            }

            $session = TrainingSession::query()
                ->whereKey($dto->sessionId)
                ->lock('FOR NO KEY UPDATE')
                ->firstOrFail();

            Student::query()
                ->whereKey($dto->studentId)
                ->lock('FOR NO KEY UPDATE')
                ->firstOrFail();

            $this->validator->validateRequest($session, $dto);

            $this->validator->validateConfirmation($session, $dto);

            $hasAvailableSeat = $this->bookingQuery
                ->hasAvailableSeat($session);

            $wallet = Wallet::query()
                ->where('student_id', $dto->studentId)
                ->lock('FOR NO KEY UPDATE')
                ->firstOrFail();

            $booking = Booking::query()->create([
                'session_id' => $session->id,
                'student_id' => $dto->studentId,
                'status' => BookingStatus::Waitlisted,
                'created_at' => now(),
            ]);

            if ($hasAvailableSeat) {
                $this->confirmationService->confirm(
                    booking: $booking,
                    session: $session,
                    wallet: $wallet
                );
            }

            $isConfirmed = $booking->status === BookingStatus::Confirmed;

            $body = [
                'booking_id' => $booking->id,
                'status' => $booking->status->value,
                'charged_amount' => $isConfirmed
                    ? $booking->charged_amount
                    : '0.00',
                'refunded_amount' => '0.00',
                'promoted_booking_id' => null,
                'wallet_balance' => $wallet->balance,
            ];

            $result = [
                'http_status' => $isConfirmed ? 201 : 202,
                'body' => $body,
            ];
            
            $this->idempotencyService->complete($idempotency, $result);

            return $result;
        }, attempts: 5);
    }
}
