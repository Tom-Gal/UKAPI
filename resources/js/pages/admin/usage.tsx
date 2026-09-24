import { Head } from '@inertiajs/react';
import { BarChart3 } from 'lucide-react';
import { AdminNav } from '@/components/admin-nav';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';
export default function AdminUsage() {
    return (
        <>
            <Head title="Admin usage" />
            <DashboardPageHeader
                eyebrow="Internal"
                title="Usage"
                description="Account and endpoint usage will come from durable daily rollups after the API gateway is enabled."
            />
            <AdminNav />
            <main className="p-5 lg:p-8">
                <EmptyState title="No usage rollups">
                    <BarChart3 className="mx-auto size-5 text-[#1248e8]" />
                    <p className="mt-2">
                        The API metering service is not in scope for this
                        frontend/auth phase.
                    </p>
                </EmptyState>
            </main>
        </>
    );
}
AdminUsage.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin' },
        { title: 'Usage', href: '/admin/usage' },
    ],
};
