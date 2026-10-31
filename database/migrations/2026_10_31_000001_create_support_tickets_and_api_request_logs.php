<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('request_id', 68)->index();
            $table->string('method', 10);
            $table->string('route_path', 255)->nullable();
            $table->string('route_name', 160)->nullable();
            $table->string('endpoint', 100)->index();
            $table->unsignedSmallInteger('status');
            $table->unsignedInteger('duration_ms');
            $table->string('error_code', 80)->nullable();
            $table->string('error_reason', 120)->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->timestamps();
            $table->index(['request_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('request_id', 68)->nullable()->index();
            $table->string('title', 160);
            $table->text('notes');
            $table->string('status', 16)->default('open')->index();
            $table->foreignId('opened_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('api_request_logs');
    }
};
