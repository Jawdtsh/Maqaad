<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require dirname(__DIR__, 2).'/vendor/autoload.php';

try {
    [
        $script,
        $uri,
        $studentId,
        $key,
        $encodedBody,
        $directory,
        $index,
    ] = $argv;

    $app = require dirname(__DIR__, 2).'/bootstrap/app.php';

    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    // Hold every independent process until the same release signal.
    file_put_contents($directory.'/ready-'.$index, '1');

    $deadline = microtime(true) + 100;

    while (! is_file($directory.'/release')) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException(
                'Timed out waiting for concurrent request release.'
            );
        }

        usleep(10_000);
    }

    $request = Request::create(
        $uri,
        'POST',
        [],
        [],
        [],
        [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_STUDENT_ID' => $studentId,
            'HTTP_IDEMPOTENCY_KEY' => $key,
            'CONTENT_TYPE' => 'application/json',
        ],
        base64_decode($encodedBody, true)
    );

    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);

    $body = json_decode(
        $response->getContent(),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    echo json_encode([
        'status' => $response->getStatusCode(),
        'body' => $body,
    ], JSON_THROW_ON_ERROR);

} catch (Throwable $exception) {
    fwrite(
        STDERR,
        $exception::class.': '.$exception->getMessage().PHP_EOL
    );

    exit(1);
}