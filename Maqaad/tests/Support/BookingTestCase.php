<?php

namespace Tests\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\ConcurrentBookingRunner;
use Tests\TestCase;

abstract class BookingTestCase extends TestCase
{
    protected ConcurrentBookingRunner $runner;

    protected function setUp(): void
    {
        parent::setUp();

        $database = getenv('TEST_DB_DATABASE') ?: 'maqad_test';

        if (! preg_match('/_test(?:ing)?$/', $database)) {
            throw new RuntimeException(
                'The test database name must end in _test or _testing.'
            );
        }

        config()->set('database.default', 'pgsql');
        config()->set(
            'database.connections.pgsql.database',
            $database
        );

        DB::purge('pgsql');

        // Separate connections must see committed fixtures. Do not use RefreshDatabase.
        Artisan::call('migrate:fresh', [
            '--database' => 'pgsql',
            '--force' => true,
        ]);

        Artisan::call('db:seed', [
            '--database' => 'pgsql',
            '--force' => true,
        ]);

        $this->runner = new ConcurrentBookingRunner();
    }

    protected function sessionId(string $price): int
    {
        $id = DB::table('sessions')
            ->where('price', $price)
            ->value('id');

        if ($id === null) {
            throw new RuntimeException(
                "Required seeded session with price {$price} was not found."
            );
        }

        return (int) $id;
    }

    protected function bookingUri(int $sessionId): string
    {
        foreach (app('router')->getRoutes() as $route) {
            if (
                in_array('POST', $route->methods(), true)
                && preg_match(
                    '~(?:^|/)sessions/\{[^}]+\}/bookings$~',
                    $route->uri()
                )
            ) {
                return '/'.preg_replace(
                    '/\{[^}]+\}/',
                    (string) $sessionId,
                    $route->uri()
                );
            }
        }

        throw new RuntimeException(
            'POST /sessions/{id}/bookings route was not found.'
        );
    }

    protected function responseCount(
        array $responses,
        int $status,
        ?string $bookingStatus = null
    ): int {
        return count(array_filter(
            $responses,
            fn ($response) =>
                $response['status'] === $status
                && (
                    $bookingStatus === null
                    || ($response['body']['status'] ?? null) === $bookingStatus
                )
        ));
    }

    protected function bookingCount(
        int $sessionId,
        string $status
    ): int {
        return DB::table('bookings')
            ->where('session_id', $sessionId)
            ->where('status', $status)
            ->count();
    }

    protected function chargesForSession(int $sessionId): Collection
    {
        return DB::table('wallet_transactions')
            ->where('type', 'booking_charge')
            ->whereIn(
                'booking_id',
                DB::table('bookings')
                    ->select('id')
                    ->where('session_id', $sessionId)
            )
            ->get();
    }

    protected function createStudentWithBalance(string $amount): int
    {
        $studentId = (int) DB::table('students')->insertGetId([
            'full_name' => 'Concurrency Test Student',
            'phone' => '0999999999',
        ]);

        $walletId = (int) DB::table('wallets')->insertGetId([
            'student_id' => $studentId,
            'balance' => $amount,
        ]);

        DB::table('wallet_transactions')->insert([
            'wallet_id' => $walletId,
            'booking_id' => null,
            'type' => 'topup',
            'amount' => $amount,
            'operation_key' => "test:student:{$studentId}:topup",
        ]);

        return $studentId;
    }
}