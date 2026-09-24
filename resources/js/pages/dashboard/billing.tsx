import { Head, Link } from '@inertiajs/react';
import { Check, CreditCard, ExternalLink } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { DashboardPageHeader } from '@/components/dashboard-page-header';

export default function Billing({
    plan,
    paidPlans,
    hasStripeCustomer,
    hasActiveSubscription,
    checkoutStatus,
}: {
    plan: Plan;
    paidPlans: Plan[];
    hasStripeCustomer: boolean;
    hasActiveSubscription: boolean;
    checkoutStatus?: 'success' | 'cancelled';
}) {
    return (
        <>
            <Head title="Billing" />
            <DashboardPageHeader
                eyebrow="Billing"
                title="Plan & billing"
                description="Manage your subscription through Stripe. Plan limits update after Stripe confirms the subscription webhook."
            />
            <main className="space-y-6 p-5 lg:p-8">
                {checkoutStatus === 'success' && (
                    <Notice>
                        Thanks — Stripe is confirming your subscription. Your
                        plan will update here as soon as the webhook arrives.
                    </Notice>
                )}
                {checkoutStatus === 'cancelled' && (
                    <Notice>Your Stripe Checkout session was cancelled.</Notice>
                )}
                <section className="rounded-xl border border-slate-200 bg-white p-6">
                    <p className="text-xs font-bold tracking-[.14em] text-slate-500 uppercase">
                        Current plan
                    </p>
                    <h2 className="mt-3 text-3xl font-semibold">
                        {plan.label}
                    </h2>
                    <p className="mt-2 text-sm text-slate-600">
                        {plan.monthly_request_quota.toLocaleString()}{' '}
                        requests/month · {plan.burst_requests_per_second}{' '}
                        requests/second
                    </p>
                    {hasStripeCustomer && (
                        <StripeRedirectForm
                            action="/billing/portal"
                            className="mt-6 inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-800 hover:border-slate-400"
                        >
                            Manage billing in Stripe
                            <ExternalLink className="size-4" />
                        </StripeRedirectForm>
                    )}
                </section>
                {!hasActiveSubscription && (
                    <section>
                        <h2 className="text-lg font-semibold">Upgrade plan</h2>
                        <p className="mt-1 text-sm text-slate-600">
                            Secure checkout, invoices, and payment methods are
                            handled by Stripe.
                        </p>
                        <div className="mt-4 grid gap-4 lg:grid-cols-3">
                            {paidPlans.map((paidPlan) => (
                                <article
                                    key={paidPlan.key}
                                    className="flex flex-col rounded-xl border border-slate-200 bg-white p-5"
                                >
                                    <h3 className="font-semibold">
                                        {paidPlan.label}
                                    </h3>
                                    <p className="mt-2 font-mono text-sm text-[#1248e8]">
                                        {paidPlan.monthly_request_quota.toLocaleString()}{' '}
                                        requests/month
                                    </p>
                                    <p className="mt-2 text-sm text-slate-600">
                                        {paidPlan.burst_requests_per_second}{' '}
                                        requests/second
                                    </p>
                                    <StripeRedirectForm
                                        action="/billing/checkout"
                                        plan={paidPlan.key}
                                        className="mt-5 inline-flex items-center justify-center gap-2 rounded-lg bg-[#1248e8] px-3.5 py-2 text-sm font-semibold text-white hover:bg-[#0f3dc4] disabled:cursor-not-allowed disabled:bg-slate-300"
                                    >
                                        <CreditCard className="size-4" />
                                        Choose {paidPlan.label}
                                    </StripeRedirectForm>
                                </article>
                            ))}
                        </div>
                    </section>
                )}
                {hasActiveSubscription && (
                    <section className="rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm text-slate-700">
                        <Check className="mb-2 size-5 text-[#1248e8]" />
                        Your active plan is managed in the Stripe billing
                        portal, where you can change plans or cancel.
                    </section>
                )}
                <Link
                    href="/pricing"
                    className="inline-flex rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-800 hover:border-slate-400"
                >
                    View plans
                </Link>
            </main>
        </>
    );
}
Billing.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Billing', href: '/billing' },
    ],
};

type Plan = {
    key: string;
    label: string;
    stripe_price: string | null;
    monthly_request_quota: number;
    burst_requests_per_second: number;
};

function Notice({ children }: { children: ReactNode }) {
    return (
        <div className="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-slate-700">
            {children}
        </div>
    );
}

function StripeRedirectForm({
    action,
    plan,
    className,
    children,
}: {
    action: string;
    plan?: string;
    className: string;
    children: ReactNode;
}) {
    const [submitting, setSubmitting] = useState(false);
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    return (
        <form action={action} method="post" onSubmit={() => setSubmitting(true)}>
            <input name="_token" type="hidden" value={csrfToken ?? ''} />
            {plan && <input name="plan" type="hidden" value={plan} />}
            <button type="submit" disabled={submitting} className={className}>
                {children}
            </button>
        </form>
    );
}
