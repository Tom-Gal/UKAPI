import { Head, Link } from '@inertiajs/react';
import { CreditCard } from 'lucide-react';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';

export default function Billing({
    plan,
    monthlyQuota,
}: {
    billingEnabled: boolean;
    plan: string;
    monthlyQuota: number;
}) {
    return (
        <>
            <Head title="Billing" />
            <DashboardPageHeader
                eyebrow="Billing"
                title="Plan & billing"
                description="Plans and quotas are visible now; checkout and the customer billing portal are deliberately not connected yet."
            />
            <main className="grid gap-6 p-5 lg:grid-cols-2 lg:p-8">
                <section className="rounded-xl border border-slate-200 bg-white p-6">
                    <p className="text-xs font-bold tracking-[.14em] text-slate-500 uppercase">
                        Current plan
                    </p>
                    <h2 className="mt-3 text-3xl font-semibold">{plan}</h2>
                    <p className="mt-2 text-sm text-slate-600">
                        {monthlyQuota.toLocaleString()} requests/month
                    </p>
                    <Link
                        href="/pricing"
                        className="mt-6 inline-flex rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-800 hover:border-slate-400"
                    >
                        View plans
                    </Link>
                </section>
                <EmptyState title="Checkout is not connected">
                    <CreditCard className="mx-auto size-5 text-[#1248e8]" />
                    <p className="mt-2">
                        Stripe and Laravel Cashier integration are intentionally
                        deferred. No payment method or subscription data is
                        collected by this build.
                    </p>
                </EmptyState>
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
