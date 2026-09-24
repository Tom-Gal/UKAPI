<?php

namespace App\Http\Controllers;

use App\Domain\Billing\PlanCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function checkout(Request $request, PlanCatalog $plans): RedirectResponse
    {
        $request->validate([
            'plan' => ['required', 'string', Rule::in(array_map(fn (array $plan): string => $plan['key'], $plans->paid()))],
        ]);

        $plan = $plans->checkoutable((string) $request->input('plan'));

        if ($plan === null) {
            abort(422, 'This plan is not configured for checkout.');
        }

        $user = $request->user();

        if ($user->subscribed('default')) {
            return $user->redirectToBillingPortal(route('billing'));
        }

        return $user->newSubscription('default', $plan['stripe_price'])
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => route('billing', ['checkout' => 'success']),
                'cancel_url' => route('billing', ['checkout' => 'cancelled']),
                'tax_id_collection' => ['enabled' => true],
                'customer_update' => ['address' => 'auto'],
            ])->redirect();
    }

    public function portal(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->hasStripeId(), 422, 'There is no Stripe billing profile for this account yet.');

        return $user->redirectToBillingPortal(route('billing'));
    }
}
