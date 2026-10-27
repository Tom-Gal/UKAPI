<?php

namespace App\Http\Controllers;

use App\Domain\Health\HealthStatus;
use App\Domain\Operations\AdminOperations;
use App\Models\ApiKey;
use App\Models\DatasetSnapshotHead;
use App\Models\ServiceHealthCheck;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function overview(HealthStatus $health, AdminOperations $operations): Response
    {
        $providers = $this->providerStatus($health);

        return Inertia::render('admin/overview', [
            'usersCount' => User::count(),
            'activeKeysCount' => ApiKey::query()->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->count(),
            'providersCount' => count(array_filter(
                $providers,
                fn (array $provider): bool => ! in_array($provider['configuration'], ['Credential missing', 'Snapshot missing'], true),
            )),
            'operations' => $operations->headline(),
        ]);
    }

    public function users(AdminOperations $operations): Response
    {
        return Inertia::render('admin/users', [
            'users' => $operations->users(),
        ]);
    }

    public function providers(HealthStatus $health): Response
    {
        return Inertia::render('admin/providers', [
            'providers' => $this->providerStatus($health),
        ]);
    }

    public function operations(AdminOperations $operations): Response
    {
        return Inertia::render('admin/operations', $operations->dashboard());
    }

    /** @return list<array<string, mixed>> */
    private function providerStatus(HealthStatus $healthStatus): array
    {
        $sicSnapshotPath = config('ukapi.sic_reference.snapshot_path');
        $sicSeedSnapshotPath = config('ukapi.sic_reference.seed_snapshot_path');
        $sicSnapshotAvailable = DatasetSnapshotHead::query()
            ->where('dataset', 'companies_house.sic_2007')
            ->where('scope_key', 'reference')
            ->exists()
            || (is_string($sicSnapshotPath) && is_file($sicSnapshotPath))
            || (is_string($sicSeedSnapshotPath) && is_file($sicSeedSnapshotPath));

        $health = collect($healthStatus->services())->keyBy('code');
        $checks = ServiceHealthCheck::query()->get()->keyBy('service');
        $provider = static function (string $code, string $healthCode, string $scope, string $configuration) use ($health, $checks): array {
            $observation = $health->get($healthCode);
            $check = $checks->get($healthCode);

            return [
                'code' => $code,
                'scope' => $scope,
                'configuration' => $configuration,
                'health' => is_array($observation) ? (string) $observation['state'] : 'unknown',
                'health_detail' => is_array($observation) ? (string) $observation['detail'] : 'Not yet measured',
                'latency_ms' => $check instanceof ServiceHealthCheck ? $check->latency_ms : null,
                'consecutive_failures' => $check instanceof ServiceHealthCheck ? $check->consecutive_failures : 0,
                'last_checked_at' => $check instanceof ServiceHealthCheck
                    ? $check->last_checked_at?->toIso8601String()
                    : null,
            ];
        };

        $motConfigured = filled(config('ukapi.mot_history.api_key'))
            && filled(config('ukapi.mot_history.client_id'))
            && filled(config('ukapi.mot_history.client_secret'))
            && filled(config('ukapi.mot_history.token_url'))
            && filled(config('ukapi.mot_history.scope'));

        return [
            $provider('postcodes_io', 'postcodes', 'UK', 'No credential required'),
            $provider('police_uk', 'crime', 'England, Wales and Northern Ireland; Scotland partial', 'No credential required'),
            $provider('environment_agency_flood_monitoring', 'flood', 'England', 'No credential required'),
            $provider(
                'companies_house',
                'companies',
                'UK',
                filled(config('ukapi.companies_house.api_key'))
                    ? 'Credential set'
                    : 'Credential missing',
            ),
            $provider(
                'dvsa_mot_history',
                'mot',
                'Great Britain and Northern Ireland',
                $motConfigured ? 'Credential set' : 'Credential missing',
            ),
            $provider(
                'companies_house_sic_2007',
                'sic',
                'UK',
                $sicSnapshotAvailable
                    ? 'Snapshot available'
                    : 'Snapshot missing',
            ),
        ];
    }
}
