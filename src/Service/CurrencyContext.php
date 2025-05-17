<?php

namespace App\Service;

class CurrencyContext
{
    private string $currency = 'eur';

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCurrencyWithSymbol(): string
    {
        return match ($this->currency) {
            'eur' => '€',
            default => strtoupper($this->currency),
        };
    }
}
