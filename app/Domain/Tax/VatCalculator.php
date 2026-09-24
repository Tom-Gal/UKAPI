<?php

namespace App\Domain\Tax;

final class VatCalculator
{
    /**
     * @return array{amount: string, rate_percent: string, vat_amount: string, total_amount: string, currency: string}
     */
    public function calculate(string $amount, string $rate): array
    {
        $amountInMinorUnits = $this->toHundredths($amount);
        $rateInHundredths = $this->toHundredths($rate);
        $vatInMinorUnits = $this->divideAndRound($amountInMinorUnits * $rateInHundredths, 10_000);

        return [
            'amount' => $this->formatHundredths($amountInMinorUnits),
            'rate_percent' => $this->formatHundredths($rateInHundredths),
            'vat_amount' => $this->formatHundredths($vatInMinorUnits),
            'total_amount' => $this->formatHundredths($amountInMinorUnits + $vatInMinorUnits),
            'currency' => 'GBP',
        ];
    }

    /**
     * @return array{gross_amount: string, rate_percent: string, vat_amount: string, net_amount: string, currency: string}
     */
    public function remove(string $amount, string $rate): array
    {
        $grossAmountInMinorUnits = $this->toHundredths($amount);
        $rateInHundredths = $this->toHundredths($rate);
        $netAmountInMinorUnits = $this->divideAndRound(
            $grossAmountInMinorUnits * 10_000,
            10_000 + $rateInHundredths,
        );

        return [
            'gross_amount' => $this->formatHundredths($grossAmountInMinorUnits),
            'rate_percent' => $this->formatHundredths($rateInHundredths),
            'vat_amount' => $this->formatHundredths($grossAmountInMinorUnits - $netAmountInMinorUnits),
            'net_amount' => $this->formatHundredths($netAmountInMinorUnits),
            'currency' => 'GBP',
        ];
    }

    private function toHundredths(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function divideAndRound(int $numerator, int $denominator): int
    {
        return intdiv($numerator + intdiv($denominator, 2), $denominator);
    }

    private function formatHundredths(int $value): string
    {
        return sprintf('%d.%02d', intdiv($value, 100), $value % 100);
    }
}
