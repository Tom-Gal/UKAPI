<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_usage', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('requests')->default(0);
            $table->timestamps();
            $table->primary(['user_id', 'date']);
        });

        Schema::create('daily_usage_rejections', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('reason', 24);
            $table->unsignedBigInteger('requests')->default(0);
            $table->timestamps();
            $table->primary(['user_id', 'date', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_usage_rejections');
        Schema::dropIfExists('daily_usage');
    }
};
