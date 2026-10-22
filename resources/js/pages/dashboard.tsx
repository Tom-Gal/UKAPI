import { Head, Link } from '@inertiajs/react';
import { ArrowRight, KeyRound } from 'lucide-react';
import { DashboardPageHeader } from '@/components/dashboard-page-header';

type Props = {
    summary: {
        active_keys: number;
        monthly_requests: number;
        monthly_quota: number;
        plan_label: string;
        first_api_success_at: string | null;
    };
};

export default function Dashboard({ summary }: Props) {
    const percentage = Math.min(
        100,
        Math.round((summary.monthly_requests / summary.monthly_quota) * 100),
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
                        className="inline-flex items-center gap-2 border border-slate-950 bg-[#13231a] px-3.5 py-2.5 text-sm font-semibold text-white shadow-[3px_3px_0_#9dbbec] hover:bg-[#2563c7]"
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
                        <span className="font-mono text-sm text-[#2563c7]">
                            {summary.monthly_requests.toLocaleString()} /{' '}
                            {summary.monthly_quota.toLocaleString()}
                        </span>
                    </div>
                    <div className="mt-5 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div
                            className="h-full bg-[#2563c7]"
                            style={{ width: `${percentage}%` }}
                        />
                    </div>
                    <Link
                        href="/usage"
                        className="mt-5 inline-flex items-center gap-1 border-b border-[#2563c7] text-sm font-semibold text-slate-950 hover:text-[#2563c7]"
                    >
                        View usage details <ArrowRight className="size-4" />
                    </Link>
                </section>
                <section className="rounded-xl border border-slate-200 bg-white p-6">
                    <p className="text-xs font-bold tracking-[.14em] text-slate-500 uppercase">
                        First request checklist
                    </p>
                    <h2 className="mt-2 text-xl font-semibold">
                        {summary.first_api_success_at
                            ? 'You are connected'
                            : 'Get from signup to data in three steps'}
                    </h2>
                    <div className="mt-5 grid gap-3 md:grid-cols-3">
                        <OnboardingStep
                            complete={summary.active_keys > 0}
                            number="1"
                            title="Create a test key"
                            href="/api-keys"
                        />
                        <OnboardingStep
                            complete={summary.first_api_success_at !== null}
                            number="2"
                            title="Open the quickstart"
                            href="/docs#quickstart"
                        />
                        <OnboardingStep
                            complete={summary.first_api_success_at !== null}
                            number="3"
                            title="Make your first request"
                            href="/docs#quickstart"
                        />
                    </div>
                    <p className="mt-5 text-sm text-slate-600">
                        Keys are shown once and stored as hashes. Test and live
                        keys use the same API format; keep both on your server,
                        never in browser code.
                    </p>
                </section>
            </main>
        </>
    );
}

function OnboardingStep({
    complete,
    number,
    title,
    href,
}: {
    complete?: boolean;
    number: string;
    title: string;
    href: string;
}) {
    return (
        <Link
            href={href}
            className={`border p-4 transition hover:border-[#2563c7] ${complete ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50'}`}
        >
            <span className="font-mono text-xs font-bold text-[#2563c7]">
                {complete ? '✓' : number}
            </span>
            <span className="mt-3 block text-sm font-semibold text-slate-900">
                {title}
            </span>
            <span className="mt-1 block text-xs text-slate-500">
                {complete ? 'Complete' : 'Open →'}
            </span>
        </Link>
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
