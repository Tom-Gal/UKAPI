import { Head, Link } from '@inertiajs/react';
import { AdminNav } from '@/components/admin-nav';
import { DashboardPageHeader } from '@/components/dashboard-page-header';

export default function AdminOverview({
    usersCount,
    activeKeysCount,
    providersCount,
}: {
    usersCount: number;
    activeKeysCount: number;
    providersCount: number;
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
                <div className="grid gap-4 md:grid-cols-3">
                    <Card
                        label="Accounts"
                        value={usersCount}
                        link="/admin/users"
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
}: {
    label: string;
    value: number | string;
    link: string;
}) {
    return (
        <Link
            href={link}
            className="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-blue-300"
        >
            <p className="text-xs font-bold tracking-[.14em] text-slate-500 uppercase">
                {label}
            </p>
            <p className="mt-3 text-3xl font-semibold">{value}</p>
        </Link>
    );
}
AdminOverview.layout = { breadcrumbs: [{ title: 'Admin', href: '/admin' }] };
