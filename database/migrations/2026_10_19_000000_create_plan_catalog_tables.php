<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 32)->unique();
            $table->string('label', 80);
            $table->json('features');
            $table->unsignedInteger('monthly_request_quota');
            $table->unsignedInteger('burst_requests_per_second');
            $table->unsignedSmallInteger('max_page_size')->default(25);
            $table->boolean('commercial_use')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::table('plans')->insert([
            [
                'key' => 'free',
                'label' => 'Free',
                'features' => json_encode(['current_data']),
                'monthly_request_quota' => 5_000,
                'burst_requests_per_second' => 5,
                'max_page_size' => 25,
                'commercial_use' => false,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'hobby',
                'label' => 'Hobby',
                'features' => json_encode(['current_data']),
                'monthly_request_quota' => 50_000,
                'burst_requests_per_second' => 10,
                'max_page_size' => 50,
                'commercial_use' => false,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pro',
                'label' => 'Pro',
                'features' => json_encode(['current_data', 'historical_data', 'change_feeds', 'commercial_use']),
                'monthly_request_quota' => 250_000,
                'burst_requests_per_second' => 25,
                'max_page_size' => 100,
                'commercial_use' => true,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'scale',
                'label' => 'Scale',
                'features' => json_encode(['current_data', 'historical_data', 'change_feeds', 'commercial_use', 'bulk_export']),
                'monthly_request_quota' => 1_000_000,
                'burst_requests_per_second' => 50,
                'max_page_size' => 100,
                'commercial_use' => true,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
