<?php

namespace App\Modules\Booking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'student_id' => $this->header('X-Student-Id'),
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required','integer','min:1','exists:students,id',],
            'idempotency_key' => ['required','string','max:255',],
        ];
    }

    public function studentId(): int
    {
        return (int) $this->validated('student_id');
    }

    public function idempotencyKey(): string
    {
        return $this->validated('idempotency_key');
    }

    public function requestFingerprint(int $sessionId): string
    {
        return hash('sha256', json_encode([
            'method' => 'POST',
            'session_id' => $sessionId,
            'body' => $this->getContent(),
        ], JSON_THROW_ON_ERROR));
    }

}