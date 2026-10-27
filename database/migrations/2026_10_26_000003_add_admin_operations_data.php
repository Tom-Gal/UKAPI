<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_health_checks', function (Blueprint $table): void {
            $table->unsignedInteger('latency_ms')->nullable();
        });

        Schema::create('endpoint_daily_metrics', function (Blueprint $table): void {
            $table->date('date');
            $table->string('endpoint', 100);
            $table->string('status_family', 3);
            $table->unsignedInteger('latency_bucket_ms');
            $table->unsignedBigInteger('requests')->default(0);
            $table->unsignedBigInteger('total_latency_ms')->default(0);
            $table->unsignedInteger('max_latency_ms')->default(0);
            $table->timestamps();
            $table->primary(['date', 'endpoint', 'status_family', 'latency_bucket_ms']);
            $table->index(['date', 'status_family']);
        });

        Schema::create('service_health_incidents', function (Blueprint $table): void {
            $table->id();
            $table->string('service', 40)->index();
            $table->string('failure_reason', 80);
            $table->timestamp('started_at');
            $table->timestamp('last_observed_at');
            $table->timestamp('recovered_at')->nullable()->index();
            $table->unsignedInteger('occurrences')->default(1);
            $table->unsignedInteger('latest_latency_ms')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledgement_note', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('notification_delivery_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notification', 120);
            $table->string('channel', 32);
            $table->string('status', 16);
            $table->string('failure_type', 160)->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->index(['status', 'occurred_at']);
        });

        Schema::create('admin_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('subject_type', 80);
            $table->string('subject_id', 80);
            $table->string('reason', 500);
            $table->json('metadata')->nullable();
            $table->string('request_id', 64)->nullable();
            $table->timestamps();
            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_events');
        Schema::dropIfExists('notification_delivery_events');
        Schema::dropIfExists('service_health_incidents');
        Schema::dropIfExists('endpoint_daily_metrics');

        Schema::table('service_health_checks', function (Blueprint $table): void {
            $table->dropColumn('latency_ms');
        });
    }
};
