<?php

namespace App\Http\Controllers;

use App\Domain\Billing\PlanCatalog;
use App\Domain\Billing\StripeSubscriptionSynchronizer;
use App\Domain\Usage\Entitlements;
use App\Domain\Usage\UsageMeter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Invoice;

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
            'breakdown' => $usageMeter->currentMonthlyBreakdownForUser($user->id),
        ]);
    }

    public function billing(
        Request $request,
        PlanCatalog $plans,
        StripeSubscriptionSynchronizer $subscriptionSynchronizer,
    ): Response {
        $user = $request->user();
        $checkoutSynchronized = false;

        if ($request->query('checkout') === 'success' && is_string($request->query('session_id'))) {
            try {
                $checkoutSynchronized = $subscriptionSynchronizer->synchronizeCheckoutSession(
                    $user,
                    $request->query('session_id'),
                );
            } catch (\Throwable $exception) {
                Log::warning('Unable to reconcile a completed Stripe Checkout session.', [
                    'user_id' => $user->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return Inertia::render('dashboard/billing', [
            'plan' => $plans->forUser($user),
            'paidPlans' => array_values(array_filter(
                $plans->paid(),
                fn (array $plan): bool => filled($plan['stripe_price']),
            )),
            'hasStripeCustomer' => $user->hasStripeId(),
            'hasActiveSubscription' => $user->subscribed('default'),
            'checkoutStatus' => $request->query('checkout'),
            'checkoutSynchronized' => $checkoutSynchronized,
            ...$this->invoicesFor($user),
        ]);
    }

    /**
     * @return array{invoices: list<array{id: string, number: string, date: string, total: string, status: string, url: string|null}>, invoicesAvailable: bool}
     */
    private function invoicesFor(User $user): array
    {
        if (! $user->hasStripeId()) {
            return ['invoices' => [], 'invoicesAvailable' => true];
        }

        try {
            $invoices = [];

            foreach ($user->invoices(true, ['limit' => 12]) as $invoice) {
                $invoices[] = $this->invoiceForDashboard($invoice);
            }

            return ['invoices' => $invoices, 'invoicesAvailable' => true];
        } catch (\Throwable $exception) {
            Log::warning('Unable to load Stripe invoices for billing dashboard.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);

            return ['invoices' => [], 'invoicesAvailable' => false];
        }
    }

    /** @return array{id: string, number: string, date: string, total: string, status: string, url: string|null} */
    private function invoiceForDashboard(Invoice $invoice): array
    {
        $stripeInvoice = $invoice->asStripeInvoice();

        return [
            'id' => $stripeInvoice->id,
            'number' => $stripeInvoice->number ?? $stripeInvoice->id,
            'date' => $invoice->date(config('app.timezone'))->format('j M Y'),
            'total' => $invoice->total(),
            'status' => ucfirst((string) $stripeInvoice->status),
            'url' => $stripeInvoice->hosted_invoice_url,
        ];
    }
}
