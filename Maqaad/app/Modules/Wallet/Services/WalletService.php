<?php

namespace App\Modules\Wallet\Services;

use App\Modules\Booking\Exceptions\BookingValidationException;
use App\Modules\Shared\Support\Money;
use App\Modules\Wallet\Enums\WalletTransactionType;
use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use LogicException;

final class WalletService
{
    public function ensureSufficientBalance(Wallet $wallet,int $amountCents): void 
    {
        if (Money::toCents($wallet->balance) < $amountCents) {
            throw BookingValidationException::insufficientBalance();
        }
    }

    public function chargeBooking(Wallet $wallet,int $bookingId,int $amountCents): void 
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException(
                'Wallet operations require a database transaction.'
            );
        }

        if ($amountCents < 0) {
            throw new LogicException(
                'Booking charge cannot be negative.'
            );
        }

        if ($amountCents === 0) {
            return;
        }

        $this->ensureSufficientBalance(
            $wallet,
            $amountCents
        );

        $newBalance = Money::toCents($wallet->balance) - $amountCents;

        WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'booking_id' => $bookingId,
            'type' => WalletTransactionType::BookingCharge,
            'amount' => Money::fromCents(-$amountCents),
            'operation_key' => "booking:{$bookingId}:charge",
        ]);

        $wallet->balance = Money::fromCents($newBalance);
        $wallet->save();
    }
}