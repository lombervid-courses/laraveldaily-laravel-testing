<?php

declare(strict_types=1);

namespace App\Services;

class CurrencyService
{
    public const RATES = [
        'usd' => [
            'eur' => 0.98
        ]
    ];

    public function convert(float $amount, string $currencyFrom, string $currencyTo): float
    {
        $rate = self::RATES[$currencyFrom][$currencyTo] ?? 0;

        return round($amount * $rate, 2);
    }
}
