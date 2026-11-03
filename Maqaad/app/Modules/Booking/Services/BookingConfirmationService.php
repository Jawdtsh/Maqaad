<?php

namespace App\Modules\Booking\Services;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Session\Models\TrainingSession;
use App\Modules\Shared\Support\Money;
use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Services\WalletService;

final class BookingConfirmationService
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    public function confirm(Booking $booking,TrainingSession $session,Wallet $wallet): void
    {
        $session->loadMissing('center');

        $priceCents = Money::toCents($session->price);

        $commissionRate = $session->center->commission_rate;

        $commissionCents = Money::percentage(
            $priceCents,
            $commissionRate
        );

        $this->walletService->ensureSufficientBalance(
            $wallet,
            $priceCents
        );

        $booking->status = BookingStatus::Confirmed;
        $booking->charged_amount = Money::fromCents($priceCents);
        $booking->commission_rate_snapshot = $commissionRate;
        $booking->commission_amount = Money::fromCents($commissionCents);

        $booking->confirmed_at = now();
        $booking->save();

        $this->walletService->chargeBooking(
            wallet: $wallet,
            bookingId: $booking->id,
            amountCents: $priceCents
        );
    }
}