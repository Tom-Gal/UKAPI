<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table): void {
            // Null scopes preserve unrestricted access for every existing key.
            $table->json('scopes')->nullable();
            $table->json('ip_allow_list')->nullable();
            $table->string('last_used_endpoint_family', 40)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('rotated_from_id')->nullable()->constrained('api_keys')->nullOnDelete();
        });

        Schema::table('api_key_audit_events', function (Blueprint $table): void {
            $table->json('details')->nullable();
        });

        foreach (['pro', 'scale'] as $planKey) {
            $plan = DB::table('plans')->where('key', $planKey)->first();
            if (! $plan) {
                continue;
            }

            $features = json_decode((string) $plan->features, true);
            $features = is_array($features) ? $features : [];
            if (! in_array('ip_allow_lists', $features, true)) {
                $features[] = 'ip_allow_lists';
            }

            DB::table('plans')->where('key', $planKey)->update([
                'features' => json_encode(array_values($features), JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (['pro', 'scale'] as $planKey) {
            $plan = DB::table('plans')->where('key', $planKey)->first();
            if (! $plan) {
                continue;
            }

            $features = json_decode((string) $plan->features, true);
            $features = is_array($features) ? $features : [];
            $features = array_values(array_filter(
                $features,
                static fn (mixed $feature): bool => $feature !== 'ip_allow_lists',
            ));
            DB::table('plans')->where('key', $planKey)->update([
                'features' => json_encode($features, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
        }

        Schema::table('api_key_audit_events', function (Blueprint $table): void {
            $table->dropColumn('details');
        });

        Schema::table('api_keys', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('rotated_from_id');
            $table->dropColumn([
                'scopes',
                'ip_allow_list',
                'last_used_endpoint_family',
                'expires_at',
            ]);
        });
    }
};
