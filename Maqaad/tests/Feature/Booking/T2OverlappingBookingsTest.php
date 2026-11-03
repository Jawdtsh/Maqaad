<?php

namespace Tests\Feature\Booking;

use App\Modules\Shared\Support\Money;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\BookingTestCase;

final class T2OverlappingBookingsTest extends BookingTestCase
{
    #[Test]
    #[TestDox('T2 | Five concurrent overlapping requests create one booking and one charge')]
    public function T2_concurrent_overlapping_bookings_charge_once(): void
    {
        $studentId = $this->createStudentWithBalance('60.00');

        $firstSessionId = $this->sessionId('50.00');
        $secondSessionId = $this->sessionId('60.00');

        $requests = [];

        $targetSessions = [
            $firstSessionId,
            $secondSessionId,
            $firstSessionId,
            $secondSessionId,
            $firstSessionId,
        ];

        foreach ($targetSessions as $index => $sessionId) {
            $requests[] = [
                'student_id' => $studentId,
                'key' => 'T2-'.$index.'-'.bin2hex(random_bytes(8)),
                'body' => '{}',
                'uri' => $this->bookingUri($sessionId),
            ];
        }

        $responses = $this->runner->runMany($requests);

        $this->assertCount(5, $responses);

        $this->assertSame(
            1,
            $this->responseCount($responses, 201, 'confirmed')
        );

        $this->assertSame(
            4,
            $this->responseCount($responses, 409)
        );

        $confirmed = DB::table('bookings')
            ->where('student_id', $studentId)
            ->where('status', 'confirmed')
            ->first();

        $this->assertNotNull($confirmed);

        $this->assertSame(
            1,
            DB::table('bookings')
                ->where('student_id', $studentId)
                ->count()
        );

        $charges = DB::table('wallet_transactions')
            ->where('booking_id', $confirmed->id)
            ->where('type', 'booking_charge')
            ->get();

        $this->assertCount(1, $charges);

        $this->assertSame(
            -Money::toCents($confirmed->charged_amount),
            Money::toCents($charges->first()->amount)
        );

        $walletBalance = DB::table('wallets')
            ->where('student_id', $studentId)
            ->value('balance');

        $this->assertSame(
            6_000 - Money::toCents($confirmed->charged_amount),
            Money::toCents($walletBalance)
        );
    }
}