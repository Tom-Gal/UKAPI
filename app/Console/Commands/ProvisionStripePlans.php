<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Stripe\Price;
use Stripe\Product;
use Stripe\StripeClient;

class ProvisionStripePlans extends Command
{
    protected $signature = 'stripe:provision-plans';

    protected $description = 'Create the UKAPI recurring Stripe prices needed for test or live billing.';

    public function handle(): int
    {
        $secret = config('cashier.secret');

        if (blank($secret)) {
            $this->components->error('STRIPE_SECRET is not configured.');

            return self::FAILURE;
        }

        $stripe = new StripeClient($secret);
        $products = collect($stripe->products->all(['active' => true, 'limit' => 100])->data);

        foreach ($this->plans() as $key => $plan) {
            $product = $this->findOrCreateProduct($stripe, $products, $key, $plan['label']);

            foreach (['month' => $plan['monthly_amount'], 'year' => $plan['annual_amount']] as $interval => $amount) {
                $price = $this->findOrCreatePrice($stripe, $product, $amount, $interval);
                $this->syncLocalPrice($key, $price->id, $interval, $amount);
                $suffix = $interval === 'year' ? '_ANNUAL' : '';
                $this->line(sprintf('STRIPE_PRICE_%s%s=%s', strtoupper($key), $suffix, $price->id));
            }
        }

        return self::SUCCESS;
    }

    /** @return array<string, array{label: string, monthly_amount: int, annual_amount: int}> */
    private function plans(): array
    {
        return [
            'hobby' => ['label' => 'UKAPI Hobby', 'monthly_amount' => 900, 'annual_amount' => 9_000],
            'pro' => ['label' => 'UKAPI Pro', 'monthly_amount' => 2_900, 'annual_amount' => 29_000],
            'scale' => ['label' => 'UKAPI Scale', 'monthly_amount' => 8_900, 'annual_amount' => 89_000],
        ];
    }

    /** @param Collection<int, Product> $products */
    private function findOrCreateProduct(StripeClient $stripe, Collection $products, string $key, string $label): Product
    {
        /** @var Product|null $product */
        $product = $products->first(
            fn (Product $candidate): bool => ($candidate->metadata['ukapi_plan'] ?? null) === $key,
        );

        if ($product) {
            return $product;
        }

        return $stripe->products->create([
            'name' => $label,
            'metadata' => ['ukapi_plan' => $key],
        ]);
    }

    private function findOrCreatePrice(StripeClient $stripe, Product $product, int $amount, string $interval): Price
    {
        /** @var Collection<int, Price> $prices */
        $prices = collect($stripe->prices->all([
            'active' => true,
            'limit' => 100,
            'product' => $product->id,
        ])->data);

        /** @var Price|null $price */
        $price = $prices->first(
            fn (Price $candidate): bool => $candidate->currency === 'gbp'
                && $candidate->unit_amount === $amount
                    && $candidate->recurring?->interval === $interval,
        );

        if ($price) {
            return $price;
        }

        return $stripe->prices->create([
            'currency' => 'gbp',
            'product' => $product->id,
            'recurring' => ['interval' => $interval],
            'unit_amount' => $amount,
        ]);
    }

    private function syncLocalPrice(string $key, string $stripePrice, string $interval, int $amount): void
    {
        $plan = Plan::query()->where('key', $key)->first();

        if ($plan === null) {
            return;
        }

        PlanPrice::query()->updateOrCreate(
            ['stripe_price' => $stripePrice],
            [
                'plan_id' => $plan->id,
                'interval' => $interval,
                'amount' => $amount,
                'currency' => 'gbp',
                'active' => true,
            ],
        );
    }
}
