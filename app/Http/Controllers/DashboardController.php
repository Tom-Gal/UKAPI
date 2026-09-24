<?php

namespace App\Http\Controllers;

use App\Domain\Usage\Entitlements;
use App\Domain\Usage\UsageMeter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        Entitlements $entitlements,
        UsageMeter $usageMeter,
    ): Response {
        $user = $request->user();
        $activeKeys = $user->apiKeys()->where('status', 'active')->count();
        $plan = $entitlements->forUser($user);

        return Inertia::render('dashboard', [
            'summary' => [
                'active_keys' => $activeKeys,
                'monthly_requests' => $usageMeter->currentMonthlyUsageForUser($user->id),
                'monthly_quota' => $plan['monthly_request_quota'],
                'request_logs_available' => false,
            ],
        ]);
    }
}
