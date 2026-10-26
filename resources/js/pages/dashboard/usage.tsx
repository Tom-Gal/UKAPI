import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    Download,
    Gauge,
    ShieldX,
    TrendingUp,
} from 'lucide-react';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';

type UsageBreakdown = {
    endpoint: string;
    label: string;
    requests: number;
};

type DailyUsage = {
    date: string;
    requests: number;
    rate_limit_rejections: number;
    quota_rejections: number;
};

type UsageInsights = {
    dailyTrend: DailyUsage[];
    dailyTrackingStartedAt: string | null;
    rejections: { rate_limit: number; quota: number };
    projection: {
        requests: number;
        percentage_of_quota: number;
        expected_quota_date: string | null;
        days_elapsed: number;
        days_in_month: number;
    };
    previousMonth: {
        requests: number;
        difference: number;
        percentage_change: number | null;
        label: string;
    };
    upgradeRecommended: boolean;
};

type Props = {
    monthlyUsage: number;
    monthlyQuota: number;
    breakdown: UsageBreakdown[];
    plan: { key: string; label: string };
    insights: UsageInsights;
    cancelledPlanEndsAt: string | null;
};

export default function Usage({
    monthlyUsage,
    monthlyQuota,
    breakdown,
    plan,
    insights,
    cancelledPlanEndsAt,
}: Props) {
    const percentage = Math.min(
        100,
        Math.round((monthlyUsage / monthlyQuota) * 100),
    );
    const previousDirection =
        insights.previousMonth.difference === 0
            ? 'the same as'
            : insights.previousMonth.difference > 0
              ? 'fewer than'
              : 'more than';

    return (
        <>
            <Head title="Usage" />
            <DashboardPageHeader
                eyebrow="Usage"
                title="Request usage"
                description="Track aggregate request volume without storing request payloads, query values, response bodies, or API-key secrets."
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

                {insights.upgradeRecommended && !cancelledPlanEndsAt && (
                    <section className="mb-6 flex flex-col gap-4 border border-blue-200 bg-blue-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="font-semibold text-slate-950">
                                Your current pace may need a larger allowance
                            </p>
                            <p className="mt-1 text-sm leading-6 text-slate-700">
                                You have used {percentage}% of this month’s
                                quota and are projected to reach{' '}
                                {insights.projection.requests.toLocaleString()}{' '}
                                requests.
                            </p>
                        </div>
                        <Link
                            href="/billing"
                            className="inline-flex shrink-0 items-center gap-2 font-semibold text-[#174ea6] underline underline-offset-4"
                        >
                            Compare plans <ArrowRight className="size-4" />
                        </Link>
                    </section>
                )}

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Metric
                        icon={Gauge}
                        label="Monthly quota"
                        value={`${monthlyUsage.toLocaleString()} / ${monthlyQuota.toLocaleString()}`}
                        detail={`${percentage}% used on ${plan.label}`}
                    />
                    <Metric
                        icon={TrendingUp}
                        label="Projected month total"
                        value={insights.projection.requests.toLocaleString()}
                        detail={
                            insights.projection.expected_quota_date
                                ? `Quota may be reached by ${formatDate(insights.projection.expected_quota_date)}`
                                : `${insights.projection.percentage_of_quota}% of quota at current pace`
                        }
                    />
                    <Metric
                        icon={BarChart3}
                        label={insights.previousMonth.label}
                        value={insights.previousMonth.requests.toLocaleString()}
                        detail={`${Math.abs(insights.previousMonth.difference).toLocaleString()} ${previousDirection} this month`}
                    />
                    <Metric
                        icon={ShieldX}
                        label="Rejected this month"
                        value={(
                            insights.rejections.rate_limit +
                            insights.rejections.quota
                        ).toLocaleString()}
                        detail={`${insights.rejections.rate_limit.toLocaleString()} rate limit, ${insights.rejections.quota.toLocaleString()} quota`}
                    />
                </div>

                <div className="mt-6 overflow-hidden border border-slate-200 bg-white">
                    <div className="h-2 bg-slate-100">
                        <div
                            className={`h-full ${percentage >= 80 ? 'bg-amber-500' : 'bg-[#2563c7]'}`}
                            style={{ width: `${percentage}%` }}
                        />
                    </div>
                </div>

                <section className="mt-6 border border-slate-200 bg-white p-5 sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Daily request trend
                            </h2>
                            <p className="mt-1 text-sm text-slate-600">
                                Admitted requests by UTC calendar day.
                                {insights.dailyTrackingStartedAt &&
                                    ` Daily aggregates for this account are available from ${formatDate(insights.dailyTrackingStartedAt)}; monthly totals can include earlier requests.`}
                            </p>
                        </div>
                        <a
                            href="/usage/export.csv"
                            className="inline-flex items-center gap-2 border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 hover:border-[#2563c7] hover:text-[#174ea6]"
                        >
                            <Download className="size-4" />
                            Download aggregate CSV
                        </a>
                    </div>
                    {insights.dailyTrend.length > 0 ? (
                        <DailyChart days={insights.dailyTrend} />
                    ) : (
                        <p className="mt-6 border-t border-slate-200 pt-5 text-sm text-slate-500">
                            No daily aggregate events have been recorded this
                            month.
                        </p>
                    )}
                </section>

                {breakdown.length > 0 && (
                    <section className="mt-6 border border-slate-200 bg-white p-6">
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
                            Stable endpoint codes only. Request contents, query
                            values, responses, and credentials are not retained.
                        </p>
                    </section>
                )}

                <div className="mt-6">
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
                                : 'Every admitted API request counts toward the monthly allowance, including validation errors.'}
                        </p>
                        <Link
                            href="/docs"
                            className="mt-4 inline-flex items-center gap-1 border-b border-[#2563c7] font-semibold text-slate-950 hover:text-[#2563c7]"
                        >
                            Read the API documentation
                            <ArrowRight className="size-4" />
                        </Link>
                    </EmptyState>
                </div>
            </main>
        </>
    );
}

function Metric({
    icon: Icon,
    label,
    value,
    detail,
}: {
    icon: typeof Gauge;
    label: string;
    value: string;
    detail: string;
}) {
    return (
        <section className="border border-slate-200 bg-white p-5">
            <div className="flex items-center gap-2 text-xs font-bold tracking-[.12em] text-slate-500 uppercase">
                <Icon className="size-4" /> {label}
            </div>
            <p className="mt-3 font-mono text-2xl font-semibold text-slate-950">
                {value}
            </p>
            <p className="mt-2 text-xs leading-5 text-slate-600">{detail}</p>
        </section>
    );
}

function DailyChart({ days }: { days: DailyUsage[] }) {
    const max = Math.max(1, ...days.map((day) => day.requests));

    return (
        <div className="mt-6">
            <div
                className="flex h-48 items-end gap-1 border-b border-slate-300"
                role="img"
                aria-label="Daily admitted request totals for the current month"
            >
                {days.map((day) => (
                    <div
                        key={day.date}
                        className="group relative flex h-full min-w-0 flex-1 items-end"
                        title={`${formatDate(day.date)}: ${day.requests.toLocaleString()} requests`}
                    >
                        <div
                            className="w-full min-w-1 bg-[#2563c7] group-hover:bg-[#174ea6]"
                            style={{
                                height: `${Math.max(day.requests > 0 ? 3 : 0, (day.requests / max) * 100)}%`,
                            }}
                        />
                    </div>
                ))}
            </div>
            <div className="mt-2 flex justify-between font-mono text-[10px] text-slate-500">
                <span>{days[0] ? formatShortDate(days[0].date) : 'Start'}</span>
                <span>
                    {days.at(-1) ? formatShortDate(days.at(-1)!.date) : 'Today'}
                </span>
            </div>
        </div>
    );
}

function formatDate(value: string) {
    return new Date(`${value}T00:00:00Z`).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone: 'UTC',
    });
}

function formatShortDate(value: string) {
    return new Date(`${value}T00:00:00Z`).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        timeZone: 'UTC',
    });
}

Usage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Usage', href: '/usage' },
    ],
};
