<?php

namespace App\Modules\Booking\Services;

use App\Modules\Booking\DTOs\BookingDTO;
use App\Modules\Booking\Exceptions\BookingValidationException;
use App\Modules\Booking\Models\IdempotencyRequest;
use Illuminate\Support\Facades\DB;
use LogicException;

final class IdempotencyService
{
    public function acquire(BookingDTO $dto): IdempotencyRequest
    {
        DB::table('idempotency_requests')->insertOrIgnore([
            'student_id' => $dto->studentId,
            'idempotency_key' => $dto->idempotencyKey,
            'request_hash' => $dto->requestFingerprint,
            'created_at' => now(),
        ]);

        $request = IdempotencyRequest::query()
            ->where('student_id', $dto->studentId)
            ->where('idempotency_key', $dto->idempotencyKey)
            ->lock('FOR NO KEY UPDATE')
            ->firstOrFail();

        if ($request->request_hash !== $dto->requestFingerprint) {
            throw BookingValidationException::idempotencyKeyConflict();
        }

        return $request;
    }

    public function getPreviousResult(IdempotencyRequest $request): ?array 
    {
        if ($request->response_status === null) {
            return null;
        }

        if ($request->response_body === null) {
            throw new LogicException(
                'Completed idempotency request has no stored response.'
            );
        }

        return [
            'http_status' => $request->response_status,
            'body' => $request->response_body,
        ];
    }

    public function complete(IdempotencyRequest $request,array $result): void 
    {
        $request->response_status = $result['http_status'];
        $request->response_body = $result['body'];
        $request->save();
    }
}