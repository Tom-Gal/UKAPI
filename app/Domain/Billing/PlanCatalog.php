<?php

namespace App\Domain\Billing;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

final class PlanCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        foreach ($this->paid() as $plan) {
            foreach ($plan['stripe_prices'] as $price) {
                if ($user->subscribed('default', $price)) {
                    return $plan;
                }
            }

            if ($plan['stripe_price'] && $user->subscribed('default', $plan['stripe_price'])) {
                return $plan;
            }
        }

        return $this->free();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function paid(): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (array $plan): bool => $plan['key'] !== 'free',
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function checkoutable(string $key, string $interval = 'month'): ?array
    {
        foreach ($this->paid() as $plan) {
            $price = $plan['prices'][$interval] ?? null;

            if ($plan['key'] === $key && filled($price['stripe_price'] ?? null)) {
                $plan['stripe_price'] = $price['stripe_price'];

                return $plan;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function forStripePrice(string $price): ?array
    {
        foreach ($this->all() as $plan) {
            if (in_array($price, $plan['stripe_prices'], true)) {
                return $plan;
            }
        }

        return null;
    }

    public function hasConfiguredPrices(): bool
    {
        foreach ($this->paid() as $plan) {
            if (blank($plan['stripe_price'])) {
                return false;
            }
        }

        return true;
    }

    public function hasConfiguredAnnualPrices(): bool
    {
        foreach ($this->paid() as $plan) {
            if (blank($plan['prices']['year']['stripe_price'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return the public plan contract without Stripe identifiers or internal
     * subscription fields. Numeric values come from the same normalised
     * catalogue used by checkout and entitlement resolution.
     *
     * @return list<array{
     *     key: string,
     *     label: string,
     *     monthly_price_pence: int,
     *     annual_price_pence: int,
     *     monthly_request_quota: int,
     *     burst_requests_per_second: int,
     *     max_page_size: int,
     *     features: list<string>,
     *     commercial_use: bool
     * }>
     */
    public function publicPlans(): array
    {
        return array_values(array_map(function (array $plan): array {
            $monthlyPrice = $plan['prices']['month']['amount'] ?? null;
            $annualPrice = $plan['prices']['year']['amount'] ?? null;

            if ($plan['key'] === 'free') {
                $monthlyPrice ??= 0;
                $annualPrice ??= 0;
            } else {
                $configured = config("ukapi.paid_plans.{$plan['key']}", []);
                $monthlyPrice ??= $configured['monthly_price_pence'] ?? 0;
                $annualPrice ??= $configured['annual_price_pence'] ?? 0;
            }

            return [
                'key' => (string) $plan['key'],
                'label' => (string) $plan['label'],
                'monthly_price_pence' => (int) $monthlyPrice,
                'annual_price_pence' => (int) $annualPrice,
                'monthly_request_quota' => (int) $plan['monthly_request_quota'],
                'burst_requests_per_second' => (int) $plan['burst_requests_per_second'],
                'max_page_size' => (int) $plan['max_page_size'],
                'features' => array_values(array_map('strval', $plan['features'])),
                'commercial_use' => (bool) $plan['commercial_use'],
            ];
        }, $this->all()));
    }

    /**
     * @return array<string, mixed>
     */
    public function free(): array
    {
        return $this->all()[0];
    }

    /** @return array<int, array<string, mixed>> */
    private function all(): array
    {
        $databasePlans = $this->databasePlans();
        if ($databasePlans !== []) {
            return $databasePlans;
        }

        $configured = [
            'free' => config('ukapi.free_plan', []),
            ...config('ukapi.paid_plans', []),
        ];

        return array_map(
            fn (string $key, array $plan): array => $this->normalise($key, $plan),
            array_keys($configured),
            $configured,
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function databasePlans(): array
    {
        try {
            if (! Schema::hasTable('plans')) {
                return [];
            }

            return Plan::query()
                ->with('prices')
                ->where('active', true)
                ->orderBy('id')
                ->get()
                ->map(fn (Plan $plan): array => $this->normalise(
                    $plan->key,
                    [
                        'label' => $plan->label,
                        'features' => $plan->features,
                        'monthly_request_quota' => $plan->monthly_request_quota,
                        'burst_requests_per_second' => $plan->burst_requests_per_second,
                        'max_page_size' => $plan->max_page_size,
                        'commercial_use' => $plan->commercial_use,
                        'prices' => $plan->prices->map(fn ($price): array => [
                            'stripe_price' => $price->stripe_price,
                            'interval' => $price->interval,
                            'amount' => $price->amount,
                            'currency' => $price->currency,
                            'active' => $price->active,
                        ])->all(),
                    ],
                ))
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @param array<string, mixed> $plan
     * @return array<string, mixed>
     */
    private function normalise(string $key, array $plan): array
    {
        $configured = $key === 'free'
            ? config('ukapi.free_plan', [])
            : config("ukapi.paid_plans.{$key}", []);
        $runtimeOverrides = app()->runningUnitTests() ? $configured : [];
        $prices = [];
        $allStripePrices = [];

        foreach ($plan['prices'] ?? [] as $price) {
            if (($price['active'] ?? true) && filled($price['stripe_price'] ?? null)) {
                $allStripePrices[] = (string) $price['stripe_price'];
                $prices[$price['interval']] = $price;
            }
        }

        foreach ([
            'month' => ['stripe_price' => $configured['stripe_price'] ?? null, 'amount' => $configured['monthly_price_pence'] ?? 0],
            'year' => ['stripe_price' => $configured['annual_stripe_price'] ?? null, 'amount' => $configured['annual_price_pence'] ?? 0],
        ] as $interval => $price) {
            if (filled($price['stripe_price']) && ! isset($prices[$interval])) {
                $prices[$interval] = [
                    ...$price,
                    'interval' => $interval,
                    'currency' => 'gbp',
                    'active' => true,
                ];
            }
        }

        $legacyPrices = array_values(array_filter(array_unique(array_merge(
            $allStripePrices,
            array_filter([(string) ($configured['stripe_price'] ?? '')]),
        ))));

        return [
            'key' => $key,
            'label' => $plan['label'] ?? $configured['label'] ?? ucfirst($key),
            'stripe_price' => $prices['month']['stripe_price'] ?? null,
            'annual_stripe_price' => $prices['year']['stripe_price'] ?? null,
            'stripe_prices' => $legacyPrices,
            'prices' => $prices,
            'monthly_request_quota' => (int) ($runtimeOverrides['monthly_request_quota'] ?? $plan['monthly_request_quota'] ?? $configured['monthly_request_quota'] ?? 0),
            'burst_requests_per_second' => (int) ($runtimeOverrides['burst_requests_per_second'] ?? $plan['burst_requests_per_second'] ?? $configured['burst_requests_per_second'] ?? 0),
            'max_page_size' => (int) ($runtimeOverrides['max_page_size'] ?? $plan['max_page_size'] ?? $configured['max_page_size'] ?? 25),
            'features' => array_values($runtimeOverrides['features'] ?? $plan['features'] ?? $configured['features'] ?? ['current_data']),
            'commercial_use' => (bool) ($runtimeOverrides['commercial_use'] ?? $plan['commercial_use'] ?? $configured['commercial_use'] ?? false),
        ];
    }
}
