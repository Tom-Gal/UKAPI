<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_request_logs', function (Blueprint $table): void {
            $table->string('cache_state', 16)->nullable()->after('duration_ms');
            $table->index(['cache_state', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('api_request_logs', function (Blueprint $table): void {
            $table->dropIndex('api_request_logs_cache_state_created_at_index');
            $table->dropColumn('cache_state');
        });
    }
};
