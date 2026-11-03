<?php

namespace App\Modules\Shared\Support;

use InvalidArgumentException;

final class Money
{
    public static function toCents(string $amount): int
    {
        if (! preg_match(
            '/^(-?)([0-9]+)\.([0-9]{2})$/D',
            $amount,
            $matches
        )) {
            throw new InvalidArgumentException(
                'Invalid monetary amount.'
            );
        }

        $units = ltrim($matches[2], '0');
        $units = $units === '' ? '0' : $units;

        if (strlen($units) > 16) {
            throw new InvalidArgumentException(
                'Monetary amount exceeds NUMERIC(18,2).'
            );
        }

        $cents = ((int) $units * 100)
            + (int) $matches[3];

        return $matches[1] === '-'
            ? -$cents
            : $cents;
    }

    public static function fromCents(int $cents): string
    {
        $negative = $cents < 0;
        $absolute = abs($cents);

        return sprintf(
            '%s%d.%02d',
            $negative ? '-' : '',
            intdiv($absolute, 100),
            $absolute % 100
        );
    }

    public static function percentage(
        int $amountCents,
        string $rate
    ): int {
        if ($amountCents < 0) {
            throw new InvalidArgumentException(
                'Amount must be nonnegative.'
            );
        }

        $basisPoints = self::toCents($rate);

        if ($basisPoints < 0 || $basisPoints > 10_000) {
            throw new InvalidArgumentException(
                'Invalid percentage rate.'
            );
        }

        $whole = intdiv($amountCents, 10_000);
        $remainder = $amountCents % 10_000;

        return ($whole * $basisPoints)
            + intdiv(
                ($remainder * $basisPoints) + 5_000,
                10_000
            );
    }
}