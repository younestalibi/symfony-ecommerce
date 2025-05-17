<?php
namespace App\Twig;

use App\Service\CurrencyContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CurrencyExtension extends AbstractExtension
{
    private CurrencyContext $currencyContext;

    public function __construct(CurrencyContext $currencyContext)
    {
        $this->currencyContext = $currencyContext;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('currency', [$this, 'getCurrency']),
            new TwigFunction('currency_with_symbol', [$this, 'getCurrencyWithSymbol']),
        ];
    }

    public function getCurrency(): string
    {
        return $this->currencyContext->getCurrency();
    }

    public function getCurrencyWithSymbol(): string
    {
        return $this->currencyContext->getCurrencyWithSymbol();
    }
}
