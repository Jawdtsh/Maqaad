<?php

namespace Tests\Support;

use RuntimeException;
use Symfony\Component\Process\Process;

final class ConcurrentBookingRunner
{
    /**
     * @param array<int, array{student_id: int, key: string, body: string}> $requests
     * @return array<int, array{status: int, body: array}>
     */
    public function run(string $uri, array $requests): array
    {
        return $this->runMany(array_map(
            fn (array $request) => [...$request, 'uri' => $uri],
            $requests
        ));
    }

    /**
     * @param array<int, array{student_id: int, key: string, body: string, uri: string}> $requests
     * @return array<int, array{status: int, body: array}>
     */
    public function runMany(array $requests): array
    {
        $directory = sys_get_temp_dir()
            .'/maqad-bookings-'
            .bin2hex(random_bytes(8));

        if (! mkdir($directory, 0700)) {
            throw new RuntimeException(
                'Unable to create concurrency test directory.'
            );
        }

        $processes = [];
        $releaseFile = $directory.'/release';

        try {
            foreach ($requests as $index => $request) {
                $process = new Process([
                    PHP_BINARY,
                    base_path('tests/Support/booking_worker.php'),
                    $request['uri'],
                    (string) $request['student_id'],
                    $request['key'],
                    base64_encode($request['body']),
                    $directory,
                    (string) $index,
                ], base_path(), $this->databaseEnvironment());

                $process->setTimeout(120);
                $process->start();

                $processes[$index] = $process;
            }

            $this->waitUntilReady($directory, $processes);

            touch($releaseFile);

            $responses = [];

            foreach ($processes as $index => $process) {
                $process->wait();

                if (! $process->isSuccessful()) {
                    throw new RuntimeException(
                        "Booking worker {$index} failed: "
                        .$process->getErrorOutput()
                        .' '
                        .$process->getOutput()
                    );
                }

                $response = json_decode(
                    $process->getOutput(),
                    true
                );

                if (
                    ! is_array($response)
                    || ! isset($response['status'], $response['body'])
                    || ! is_array($response['body'])
                ) {
                    throw new RuntimeException(
                        "Booking worker {$index} returned an invalid response."
                    );
                }

                $responses[$index] = $response;
            }

            return $responses;
        } finally {
            // Release waiting workers even if setup or assertions fail.
            if (! is_file($releaseFile)) {
                touch($releaseFile);
            }

            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop(1);
                }
            }

            foreach (glob($directory.'/*') ?: [] as $file) {
                unlink($file);
            }

            rmdir($directory);
        }
    }

    /**
     * @param array<int, Process> $processes
     */
    private function waitUntilReady(
        string $directory,
        array $processes
    ): void {
        $deadline = microtime(true) + 90;

        while (
            count(glob($directory.'/ready-*') ?: [])
            < count($processes)
        ) {
            foreach ($processes as $index => $process) {
                if (! $process->isRunning()) {
                    throw new RuntimeException(
                        "Booking worker {$index} exited before synchronization: "
                        .$process->getErrorOutput()
                        .' '
                        .$process->getOutput()
                    );
                }
            }

            if (microtime(true) >= $deadline) {
                throw new RuntimeException(
                    'Booking workers did not become ready within 90 seconds.'
                );
            }

            usleep(20_000);
        }
    }

    private function databaseEnvironment(): array
    {
        $database = config('database.connections.pgsql');

        return [
            'APP_ENV' => 'testing',
            'APP_DEBUG' => 'false',
            'APP_KEY' => (string) config('app.key'),
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => (string) $database['host'],
            'DB_PORT' => (string) $database['port'],
            'DB_DATABASE' => (string) $database['database'],
            'DB_USERNAME' => (string) $database['username'],
            'DB_PASSWORD' => (string) $database['password'],
            'DB_URL' => false,
            'DATABASE_URL' => false,
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
        ];
    }
}