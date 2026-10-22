<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_usage', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('month');
            $table->unsignedBigInteger('requests')->default(0);
            $table->timestamps();
            $table->primary(['user_id', 'month']);
        });
        Schema::create('monthly_endpoint_usage', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('month');
            $table->string('endpoint', 100);
            $table->unsignedBigInteger('requests')->default(0);
            $table->primary(['user_id', 'month', 'endpoint']);
        });
        Schema::create('usage_notifications', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('month');
            $table->unsignedSmallInteger('percentage');
            $table->timestamp('claimed_at');
            $table->primary(['user_id', 'month', 'percentage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_notifications');
        Schema::dropIfExists('monthly_endpoint_usage');
        Schema::dropIfExists('monthly_usage');
    }
};
