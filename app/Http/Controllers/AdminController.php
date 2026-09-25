<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function overview(): Response
    {
        $providers = $this->providerStatus();

        return Inertia::render('admin/overview', [
            'usersCount' => User::count(),
            'activeKeysCount' => ApiKey::query()->where('status', 'active')->count(),
            'providersCount' => count(array_filter(
                $providers,
                fn (array $provider): bool => ! in_array($provider['status'], ['Credential missing', 'Snapshot missing'], true),
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

    public function providers(): Response
    {
        return Inertia::render('admin/providers', [
            'providers' => $this->providerStatus(),
        ]);
    }

    /** @return list<array{code: string, scope: string, status: string}> */
    private function providerStatus(): array
    {
        $sicSnapshotPath = config('ukapi.sic_reference.snapshot_path');
        $sicSeedSnapshotPath = config('ukapi.sic_reference.seed_snapshot_path');
        $sicSnapshotAvailable = (is_string($sicSnapshotPath) && is_file($sicSnapshotPath))
            || (is_string($sicSeedSnapshotPath) && is_file($sicSeedSnapshotPath));

        return [
            ['code' => 'postcodes_io', 'scope' => 'UK', 'status' => 'No credential required'],
            [
                'code' => 'police_uk',
                'scope' => 'England, Wales and Northern Ireland; Scotland partial',
                'status' => 'No credential required',
            ],
            [
                'code' => 'environment_agency_flood_monitoring',
                'scope' => 'England',
                'status' => 'No credential required',
            ],
            [
                'code' => 'companies_house',
                'scope' => 'UK',
                'status' => is_string(config('ukapi.companies_house.api_key')) && config('ukapi.companies_house.api_key') !== ''
                    ? 'Credential set'
                    : 'Credential missing',
            ],
            [
                'code' => 'companies_house_sic_2007',
                'scope' => 'UK',
                'status' => $sicSnapshotAvailable
                    ? 'Snapshot available'
                    : 'Snapshot missing',
            ],
        ];
    }
}
