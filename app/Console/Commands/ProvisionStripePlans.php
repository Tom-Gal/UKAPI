<?php

namespace App\Console\Commands;

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
            $price = $this->findOrCreatePrice($stripe, $product, $plan['amount']);

            $this->line(sprintf('STRIPE_PRICE_%s=%s', strtoupper($key), $price->id));
        }

        return self::SUCCESS;
    }

    /** @return array<string, array{label: string, amount: int}> */
    private function plans(): array
    {
        return [
            'hobby' => ['label' => 'UKAPI Hobby', 'amount' => 499],
            'pro' => ['label' => 'UKAPI Pro', 'amount' => 1299],
            'scale' => ['label' => 'UKAPI Scale', 'amount' => 2999],
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

    private function findOrCreatePrice(StripeClient $stripe, Product $product, int $amount): Price
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
                && $candidate->recurring?->interval === 'month',
        );

        if ($price) {
            return $price;
        }

        return $stripe->prices->create([
            'currency' => 'gbp',
            'product' => $product->id,
            'recurring' => ['interval' => 'month'],
            'unit_amount' => $amount,
        ]);
    }
}
