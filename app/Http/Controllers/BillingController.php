<?php

namespace App\Http\Controllers;

use App\Domain\Billing\PlanCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Stripe\Exception\ApiErrorException;

class BillingController extends Controller
{
    public function checkout(Request $request, PlanCatalog $plans): RedirectResponse
    {
        $request->validate([
            'plan' => ['required', 'string', Rule::in(array_map(fn (array $plan): string => $plan['key'], $plans->paid()))],
            'interval' => ['sometimes', 'string', Rule::in(['month', 'year'])],
        ]);

        $interval = (string) $request->input('interval', 'month');
        $plan = $plans->checkoutable((string) $request->input('plan'), $interval);

        if ($plan === null) {
            abort(422, 'This plan is not configured for checkout.');
        }

        $user = $request->user();

        if ($user->subscribed('default')) {
            try {
                return $user->redirectToBillingPortal(route('billing'));
            } catch (ApiErrorException $exception) {
                return $this->stripeFailure($request, $exception, 'billing portal');
            }
        }

        try {
            return $user->newSubscription('default', $plan['stripe_price'])
                ->allowPromotionCodes()
                ->checkout([
                    'success_url' => route('billing', ['checkout' => 'success']).'&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('billing', ['checkout' => 'cancelled']),
                    'tax_id_collection' => ['enabled' => true],
                    'customer_update' => ['address' => 'auto'],
                ])->redirect();
        } catch (ApiErrorException $exception) {
            return $this->stripeFailure($request, $exception, 'checkout');
        }
    }

    public function portal(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->hasStripeId(), 422, 'There is no Stripe billing profile for this account yet.');

        try {
            return $user->redirectToBillingPortal(route('billing'));
        } catch (ApiErrorException $exception) {
            return $this->stripeFailure($request, $exception, 'billing portal');
        }
    }

    private function stripeFailure(Request $request, ApiErrorException $exception, string $operation): RedirectResponse
    {
        Log::warning('Stripe billing operation failed.', [
            'operation' => $operation,
            'user_id' => $request->user()->id,
            'stripe_code' => $exception->getStripeCode(),
            'exception' => $exception::class,
        ]);

        Inertia::flash('toast', [
            'type' => 'error',
            'message' => 'Billing is temporarily unavailable. Please try again shortly.',
        ]);

        return to_route('billing');
    }
}
