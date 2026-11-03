<?php

namespace App\Modules\Booking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BookingOperationResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'booking_id' => $this->resource['booking_id'],
            'status' => $this->resource['status'],
            'charged_amount' => $this->resource['charged_amount'],
            'refunded_amount' => $this->resource['refunded_amount'],
            'promoted_booking_id' => $this->resource['promoted_booking_id'],
            'wallet_balance' => $this->resource['wallet_balance'],
        ];
    }
}