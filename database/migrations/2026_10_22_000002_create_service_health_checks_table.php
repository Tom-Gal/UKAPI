<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('service')->unique();
            $table->string('status')->default('unknown');
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('source_date')->nullable();
            $table->timestamp('reference_retrieved_at')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamp('incident_notified_at')->nullable();
            $table->timestamp('last_alert_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_health_checks');
    }
};
