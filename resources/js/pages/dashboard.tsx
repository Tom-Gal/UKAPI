import { Head, Link } from '@inertiajs/react';
import { ArrowRight, KeyRound, Zap } from 'lucide-react';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';

type Props = {
    summary: {
        active_keys: number;
        monthly_requests: number;
        monthly_quota: number;
        plan_label: string;
    };
};

export default function Dashboard({ summary }: Props) {
    const percentage = Math.round(
        (summary.monthly_requests / summary.monthly_quota) * 100,
    );
    return (
        <>
            <Head title="Dashboard" />
            <DashboardPageHeader
                eyebrow="Account overview"
                title="Your UKAPI workspace"
                description="Create a key, make a request, and return here to understand your usage."
                action={
                    <Link
                        href="/api-keys"
                        className="inline-flex items-center gap-2 rounded-lg bg-[#1248e8] px-3.5 py-2.5 text-sm font-semibold text-white hover:bg-[#0f3dc4]"
                    >
                        <KeyRound className="size-4" />
                        Manage API keys
                    </Link>
                }
            />
            <main className="space-y-6 p-5 lg:p-8">
                <div className="grid gap-4 md:grid-cols-3">
                    <Metric
                        label="Current plan"
                        value={summary.plan_label}
                        detail={`${summary.monthly_quota.toLocaleString()} requests/month`}
                    />
                    <Metric
                        label="Active API keys"
                        value={String(summary.active_keys)}
                        detail="Live and test environments"
                    />
                    <Metric
                        label="This month"
                        value={`${percentage}%`}
                        detail={`${summary.monthly_requests.toLocaleString()} of ${summary.monthly_quota.toLocaleString()} requests`}
                    />
                </div>
                <section className="rounded-xl border border-slate-200 bg-white p-6">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h2 className="font-semibold">
                                Monthly request quota
                            </h2>
                            <p className="mt-1 text-sm text-slate-600">
                                Usage is metered as public API requests are
                                accepted.
                            </p>
                        </div>
                        <span className="font-mono text-sm text-[#1248e8]">
                            {summary.monthly_requests.toLocaleString()} /{' '}
                            {summary.monthly_quota.toLocaleString()}
                        </span>
                    </div>
                    <div className="mt-5 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div
                            className="h-full rounded-full bg-[#1248e8]"
                            style={{ width: `${percentage}%` }}
                        />
                    </div>
                    <Link
                        href="/usage"
                        className="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-[#1248e8]"
                    >
                        View usage details <ArrowRight className="size-4" />
                    </Link>
                </section>
                {summary.active_keys === 0 ? (
                    <EmptyState title="Create your first API key">
                        <p>
                            Keys are displayed only once, stored as hashes, and
                            can be revoked at any time.
                        </p>
                        <Link
                            href="/api-keys"
                            className="mt-4 inline-flex items-center gap-2 font-semibold text-[#1248e8]"
                        >
                            Create a key <ArrowRight className="size-4" />
                        </Link>
                    </EmptyState>
                ) : (
                    <EmptyState title="Your next request starts here">
                        <p>
                            Start with a test key and the live VAT endpoints,
                            then return here to follow this month’s usage.
                        </p>
                        <Link
                            href="/docs"
                            className="mt-4 inline-flex items-center gap-2 font-semibold text-[#1248e8]"
                        >
                            Open documentation <Zap className="size-4" />
                        </Link>
                    </EmptyState>
                )}
            </main>
        </>
    );
}

function Metric({
    label,
    value,
    detail,
}: {
    label: string;
    value: string;
    detail: string;
}) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5">
            <p className="text-xs font-bold tracking-[.14em] text-slate-500 uppercase">
                {label}
            </p>
            <p className="mt-3 text-2xl font-semibold tracking-tight">
                {value}
            </p>
            <p className="mt-1 text-sm text-slate-600">{detail}</p>
        </div>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
};
