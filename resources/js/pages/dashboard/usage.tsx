import { Head, Link } from '@inertiajs/react';
import { ArrowRight, BarChart3 } from 'lucide-react';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';

export default function Usage({
    monthlyUsage,
    monthlyQuota,
}: {
    monthlyUsage: number;
    monthlyQuota: number;
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
                description="A running account-level quota is shown here. Endpoint and day breakdowns will follow as more API families are released."
            />
            <main className="p-5 lg:p-8">
                <div className="mb-6 rounded-xl border border-slate-200 bg-white p-6">
                    <p className="text-xs font-bold tracking-[.14em] text-slate-500 uppercase">
                        Free plan quota
                    </p>
                    <p className="mt-3 text-3xl font-semibold">
                        {monthlyUsage.toLocaleString()}{' '}
                        <span className="text-base font-medium text-slate-500">
                            / {monthlyQuota.toLocaleString()} requests
                        </span>
                    </p>
                    <div className="mt-5 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div
                            className="h-full rounded-full bg-[#1248e8] transition-[width] duration-500"
                            style={{ width: `${percentage}%` }}
                        />
                    </div>
                </div>
                <EmptyState
                    title={
                        monthlyUsage === 0
                            ? 'No usage has been recorded'
                            : 'More usage detail is on the way'
                    }
                >
                    <BarChart3 className="mx-auto size-5 text-[#1248e8]" />
                    <p className="mt-2">
                        VAT requests are live and counted. Endpoint-family and
                        daily views will arrive with the next API families.
                    </p>
                    <Link
                        href="/docs"
                        className="mt-4 inline-flex items-center gap-1 font-semibold text-[#1248e8]"
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
