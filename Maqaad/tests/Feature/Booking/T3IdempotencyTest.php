<?php

namespace Tests\Feature\Booking;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\BookingTestCase;

final class T3IdempotencyTest extends BookingTestCase
{
    #[Test]
    #[TestDox('T3 | Retrying the same idempotency key replays one response and one charge')]
    public function T3_same_idempotency_key_replays_original_response(): void
    {
        $studentId = (int) DB::table('students')
            ->orderBy('id')
            ->value('id');

        $sessionId = $this->sessionId('50.00');
        $uri = $this->bookingUri($sessionId);
        $key = 'T3-'.bin2hex(random_bytes(12));

        $request = [
            'student_id' => $studentId,
            'key' => $key,
            'body' => '{"a":1,"b":2}',
        ];

        $responses = $this->runner->run(
            $uri,
            [$request, $request]
        );

        $this->assertSame($responses[0], $responses[1]);
        $this->assertSame(201, $responses[0]['status']);
        $this->assertSame(
            'confirmed',
            $responses[0]['body']['status']
        );

        $conflict = $this->runner->run($uri, [[
            ...$request,
            'body' => '{"a":1,"b":3}',
        ]]);

        $this->assertSame(409, $conflict[0]['status']);

        $this->assertSame(
            1,
            $this->bookingCount($sessionId, 'confirmed')
        );

        $this->assertCount(
            1,
            $this->chargesForSession($sessionId)
        );

        $this->assertSame(
            '950.00',
            DB::table('wallets')
                ->where('student_id', $studentId)
                ->value('balance')
        );
    }
}