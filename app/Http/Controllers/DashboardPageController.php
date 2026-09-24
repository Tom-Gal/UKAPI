<?php

namespace App\Http\Controllers;

use App\Domain\Billing\PlanCatalog;
use App\Domain\Usage\Entitlements;
use App\Domain\Usage\UsageMeter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardPageController extends Controller
{
    public function usage(
        Request $request,
        Entitlements $entitlements,
        UsageMeter $usageMeter,
    ): Response {
        $user = $request->user();

        return Inertia::render('dashboard/usage', [
            'monthlyUsage' => $usageMeter->currentMonthlyUsageForUser($user->id),
            'monthlyQuota' => $entitlements->forUser($user)['monthly_request_quota'],
        ]);
    }

    public function billing(Request $request, PlanCatalog $plans): Response
    {
        $user = $request->user();

        return Inertia::render('dashboard/billing', [
            'plan' => $plans->forUser($user),
            'paidPlans' => array_values(array_filter(
                $plans->paid(),
                fn (array $plan): bool => filled($plan['stripe_price']),
            )),
            'hasStripeCustomer' => $user->hasStripeId(),
            'hasActiveSubscription' => $user->subscribed('default'),
            'checkoutStatus' => $request->query('checkout'),
        ]);
    }
}
