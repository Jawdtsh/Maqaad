<?php

namespace App\Modules\Wallet\Enums;

enum WalletTransactionType: string
{
    case Topup = 'topup';
    case BookingCharge = 'booking_charge';
    case StudentRefund = 'student_refund';
    case CenterRefund = 'center_refund';
}