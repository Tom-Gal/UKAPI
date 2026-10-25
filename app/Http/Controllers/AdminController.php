<?php

namespace App\Http\Controllers;

use App\Domain\Health\HealthStatus;
use App\Models\ApiKey;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function overview(HealthStatus $health): Response
    {
        $providers = $this->providerStatus($health);

        return Inertia::render('admin/overview', [
            'usersCount' => User::count(),
            'activeKeysCount' => ApiKey::query()->where('status', 'active')->count(),
            'providersCount' => count(array_filter(
                $providers,
                fn (array $provider): bool => ! in_array($provider['configuration'], ['Credential missing', 'Snapshot missing'], true),
            )),
        ]);
    }

    public function users(): Response
    {
        return Inertia::render('admin/users', [
            'users' => User::query()
                ->withCount('apiKeys')
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                    'api_keys_count' => $user->api_keys_count,
                    'created_at' => $user->created_at->toIso8601String(),
                ]),
        ]);
    }

    public function providers(HealthStatus $health): Response
    {
        return Inertia::render('admin/providers', [
            'providers' => $this->providerStatus($health),
        ]);
    }

    /** @return list<array{code: string, scope: string, configuration: string, health: string, health_detail: string}> */
    private function providerStatus(HealthStatus $healthStatus): array
    {
        $sicSnapshotPath = config('ukapi.sic_reference.snapshot_path');
        $sicSeedSnapshotPath = config('ukapi.sic_reference.seed_snapshot_path');
        $sicSnapshotAvailable = (is_string($sicSnapshotPath) && is_file($sicSnapshotPath))
            || (is_string($sicSeedSnapshotPath) && is_file($sicSeedSnapshotPath));

        $health = collect($healthStatus->services())->keyBy('code');
        $provider = static function (string $code, string $healthCode, string $scope, string $configuration) use ($health): array {
            $observation = $health->get($healthCode);

            return [
                'code' => $code,
                'scope' => $scope,
                'configuration' => $configuration,
                'health' => is_array($observation) ? (string) $observation['state'] : 'unknown',
                'health_detail' => is_array($observation) ? (string) $observation['detail'] : 'Not yet measured',
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
