<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('stripe_price')->unique();
            $table->string('interval', 16);
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('gbp');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['plan_id', 'interval', 'active']);
        });

        $paidPlans = config('ukapi.paid_plans', []);
        foreach ($paidPlans as $key => $plan) {
            $price = $plan['stripe_price'] ?? null;
            if (! is_string($price) || $price === '') {
                continue;
            }

            $planId = DB::table('plans')->where('key', $key)->value('id');
            if (! is_int($planId)) {
                continue;
            }

            DB::table('plan_prices')->insert([
                'plan_id' => $planId,
                'stripe_price' => $price,
                'interval' => 'month',
                'amount' => (int) ($plan['legacy_monthly_price_pence'] ?? $plan['monthly_price_pence'] ?? 0),
                'currency' => 'gbp',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_prices');
    }
};
