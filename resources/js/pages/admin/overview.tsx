import { Head, Link } from '@inertiajs/react';
import { AdminNav } from '@/components/admin-nav';
import { DashboardPageHeader } from '@/components/dashboard-page-header';

export default function AdminOverview({
    usersCount,
    activeKeysCount,
    providersCount,
    operations,
}: {
    usersCount: number;
    activeKeysCount: number;
    providersCount: number;
    operations: {
        open_incidents: number;
        unacknowledged_incidents: number;
        failed_jobs: number;
        notification_failures_24h: number;
        rejections_today: number;
    };
}) {
    return (
        <>
            <Head title="Admin overview" />
            <DashboardPageHeader
                eyebrow="Internal"
                title="Administration"
                description="A protected operational area. Admin privileges are explicit and must be assigned outside normal registration."
            />
            <AdminNav />
            <main className="p-5 lg:p-8">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Card
                        label="Accounts"
                        value={usersCount}
                        link="/admin/users"
                    />
                    <Card
                        label="Open incidents"
                        value={operations.open_incidents}
                        detail={`${operations.unacknowledged_incidents} awaiting acknowledgement`}
                        link="/admin/operations"
                        attention={operations.unacknowledged_incidents > 0}
                    />
                    <Card
                        label="Failed jobs"
                        value={operations.failed_jobs}
                        detail={`${operations.notification_failures_24h} notification failures in 24h`}
                        link="/admin/operations"
                        attention={operations.failed_jobs > 0}
                    />
                    <Card
                        label="Rejected today"
                        value={operations.rejections_today}
                        detail="Rate-limit and quota rejections"
                        link="/admin/operations"
                    />
                    <Card
                        label="Active API keys"
                        value={activeKeysCount}
                        link="/api-keys"
                    />
                    <Card
                        label="Configured providers"
                        value={providersCount}
                        link="/admin/providers"
                    />
                </div>
            </main>
        </>
    );
}
function Card({
    label,
    value,
    link,
    detail,
    attention = false,
}: {
    label: string;
    value: number | string;
    link: string;
    detail?: string;
    attention?: boolean;
}) {
    return (
        <Link
            href={link}
            className={`border bg-white p-5 transition hover:border-blue-300 ${attention ? 'border-rose-300' : 'border-slate-200'}`}
        >
            <p className="text-xs font-bold tracking-[.14em] text-slate-500 uppercase">
                {label}
            </p>
            <p className="mt-3 text-3xl font-semibold">{value}</p>
            {detail && (
                <p className="mt-2 text-xs leading-5 text-slate-500">
                    {detail}
                </p>
            )}
        </Link>
    );
}
AdminOverview.layout = { breadcrumbs: [{ title: 'Admin', href: '/admin' }] };
