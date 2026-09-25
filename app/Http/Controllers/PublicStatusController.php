<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class PublicStatusController extends Controller
{
    public function __invoke(): Response
    {
        $sicSnapshotPath = config('ukapi.sic_reference.snapshot_path');
        $sicSeedSnapshotPath = config('ukapi.sic_reference.seed_snapshot_path');
        $sicReferenceAvailable = (is_string($sicSnapshotPath) && is_file($sicSnapshotPath))
            || (is_string($sicSeedSnapshotPath) && is_file($sicSeedSnapshotPath));

        return Inertia::render('public/status', [
            'services' => [
                ['name' => 'Account dashboard', 'detail' => 'Available', 'state' => 'available'],
                ['name' => 'UKAPI v1', 'detail' => 'Available', 'state' => 'available'],
                ['name' => 'Postcode data', 'detail' => 'Available', 'state' => 'available'],
                ['name' => 'Crime data', 'detail' => 'Available', 'state' => 'available'],
                ['name' => 'Flood data', 'detail' => 'Available (England)', 'state' => 'available'],
                [
                    'name' => 'Company data',
                    'detail' => filled(config('ukapi.companies_house.api_key')) ? 'Available' : 'Unavailable',
                    'state' => filled(config('ukapi.companies_house.api_key')) ? 'available' : 'unavailable',
                ],
                [
                    'name' => 'SIC reference data',
                    'detail' => $sicReferenceAvailable ? 'Available' : 'Unavailable',
                    'state' => $sicReferenceAvailable ? 'available' : 'unavailable',
                ],
            ],
        ]);
    }
}
