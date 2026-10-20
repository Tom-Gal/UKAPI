<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dataset_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('dataset', 80);
            $table->string('scope_key', 180);
            $table->string('version', 26)->unique();
            $table->string('source', 80);
            $table->timestamp('captured_at');
            $table->timestamp('source_updated_at')->nullable();
            $table->string('checksum', 64);
            $table->unsignedInteger('record_count');
            $table->json('payload');
            $table->timestamps();

            $table->index(['dataset', 'scope_key', 'created_at']);
        });

        Schema::create('dataset_snapshot_heads', function (Blueprint $table): void {
            $table->id();
            $table->string('dataset', 80);
            $table->string('scope_key', 180);
            $table->foreignId('snapshot_id')->constrained('dataset_snapshots')->cascadeOnDelete();
            $table->timestamp('switched_at');
            $table->timestamps();

            $table->unique(['dataset', 'scope_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dataset_snapshot_heads');
        Schema::dropIfExists('dataset_snapshots');
    }
};
