<?php

namespace Tests\Feature\Booking;

use App\Modules\Shared\Support\Money;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\BookingTestCase;

final class T1ConcurrentBookingTest extends BookingTestCase
{
    #[Test]
    #[TestDox('T1 | 50 concurrent requests respect capacity and charge exactly 10 seats')]
    public function T1_concurrent_booking_never_exceeds_capacity(): void
    {
        $sessionId = $this->sessionId('50.00');

        $studentIds = DB::table('students')
            ->orderBy('id')
            ->limit(50)
            ->pluck('id');

        $requests = $studentIds->map(fn ($id) => [
            'student_id' => (int) $id,
            'key' => 'T1-'.bin2hex(random_bytes(12)),
            'body' => '{}',
        ])->all();

        $responses = $this->runner->run(
            $this->bookingUri($sessionId),
            $requests
        );

        $this->assertCount(50, $responses);

        $this->assertSame(
            10,
            $this->responseCount($responses, 201, 'confirmed')
        );

        $this->assertSame(
            40,
            $this->responseCount($responses, 202, 'waitlisted')
        );

        $this->assertSame(
            10,
            $this->bookingCount($sessionId, 'confirmed')
        );

        $this->assertSame(
            40,
            $this->bookingCount($sessionId, 'waitlisted')
        );

        $charges = $this->chargesForSession($sessionId);

        $this->assertCount(10, $charges);

        $this->assertSame(
            -50_000,
            array_sum(array_map(
                fn ($charge) => Money::toCents($charge->amount),
                $charges->all()
            ))
        );
    }
}