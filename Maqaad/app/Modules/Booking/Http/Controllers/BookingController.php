<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Actions\CreateBookingAction;
use App\Modules\Booking\DTOs\BookingDTO;
use App\Modules\Booking\Http\Requests\StoreBookingRequest;
use App\Modules\Booking\Http\Resources\BookingOperationResource;
use Illuminate\Http\JsonResponse;

final class BookingController extends Controller
{
    public function store(
        StoreBookingRequest $request,
        int $sessionId,
        CreateBookingAction $action
    ): JsonResponse {
        $dto = new BookingDTO(
            sessionId: $sessionId,
            studentId: $request->studentId(),
            idempotencyKey: $request->idempotencyKey(),
            requestFingerprint: $request->requestFingerprint($sessionId),
        );

        $result = $action->execute($dto);

        return (new BookingOperationResource($result['body']))
            ->response()
            ->setStatusCode($result['http_status']);
    }
}
