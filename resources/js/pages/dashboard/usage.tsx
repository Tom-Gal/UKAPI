import { Head, Link } from '@inertiajs/react';
import { ArrowRight, BarChart3 } from 'lucide-react';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';

type UsageBreakdown = {
    endpoint: string;
    label: string;
    requests: number;
};

export default function Usage({
    monthlyUsage,
    monthlyQuota,
    breakdown,
    plan,
    cancelledPlanEndsAt,
}: {
    monthlyUsage: number;
    monthlyQuota: number;
    breakdown: UsageBreakdown[];
    plan: {
        label: string;
    };
    cancelledPlanEndsAt: string | null;
}) {
    const percentage = Math.min(
        100,
        Math.round((monthlyUsage / monthlyQuota) * 100),
    );

    return (
        <>
            <Head title="Usage" />
            <DashboardPageHeader
                eyebrow="Usage"
                title="Request usage"
                description="Track the requests included in your current monthly plan."
            />
            <main className="p-5 lg:p-8">
                {cancelledPlanEndsAt && (
                    <div className="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-slate-700">
                        <p className="font-semibold text-slate-950">
                            You’re on the {plan.label} plan you cancelled.
                        </p>
                        <p className="mt-1">
                            Your {plan.label} limits remain available until{' '}
                            <strong>{cancelledPlanEndsAt}</strong>. After that,
                            your account will move to the Free plan.
                        </p>
                    </div>
                )}
                <div className="mb-6 rounded-xl border border-slate-200 bg-white p-6">
                    <p className="text-xs font-bold tracking-[.14em] text-slate-500 uppercase">
                        Monthly quota
                    </p>
                    <p className="mt-3 text-3xl font-semibold">
                        {monthlyUsage.toLocaleString()}{' '}
                        <span className="text-base font-medium text-slate-500">
                            / {monthlyQuota.toLocaleString()} requests
                        </span>
                    </p>
                    <div className="mt-5 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div
                            className="h-full bg-[#2563c7] transition-[width] duration-500"
                            style={{ width: `${percentage}%` }}
                        />
                    </div>
                </div>
                {breakdown.length > 0 && (
                    <section className="mb-6 rounded-xl border border-slate-200 bg-white p-6">
                        <div className="flex items-baseline justify-between gap-4">
                            <h2 className="text-lg font-semibold">
                                Where requests are going
                            </h2>
                            <span className="text-sm text-slate-500">
                                This month
                            </span>
                        </div>
                        <div className="mt-4 divide-y divide-slate-100">
                            {breakdown.map((item) => (
                                <div
                                    key={item.endpoint}
                                    className="flex items-center justify-between gap-4 py-3 text-sm"
                                >
                                    <span className="font-medium text-slate-800">
                                        {item.label}
                                    </span>
                                    <span className="font-mono text-slate-600">
                                        {item.requests.toLocaleString()}
                                    </span>
                                </div>
                            ))}
                        </div>
                        <p className="mt-4 text-xs text-slate-500">
                            A high-level breakdown by API endpoint. Request
                            contents and API-key secrets are never shown here.
                        </p>
                    </section>
                )}
                <EmptyState
                    title={
                        monthlyUsage === 0
                            ? 'No usage recorded yet'
                            : 'Keep building'
                    }
                >
                    <BarChart3 className="mx-auto size-5 text-[#2563c7]" />
                    <p className="mt-2">
                        {monthlyUsage === 0
                            ? 'Make your first API request to start using your monthly allowance.'
                            : 'Every successful API request counts toward the monthly allowance shown above.'}
                    </p>
                    <Link
                        href="/docs"
                        className="mt-4 inline-flex items-center gap-1 border-b border-[#2563c7] font-semibold text-slate-950 hover:text-[#2563c7]"
                    >
                        Read the API documentation
                        <ArrowRight className="size-4" />
                    </Link>
                </EmptyState>
            </main>
        </>
    );
}
Usage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Usage', href: '/usage' },
    ],
};
