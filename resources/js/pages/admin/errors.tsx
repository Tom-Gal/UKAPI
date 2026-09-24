import { Head } from '@inertiajs/react';
import { CircleAlert } from 'lucide-react';
import { AdminNav } from '@/components/admin-nav';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';
export default function AdminErrors() {
    return (
        <>
            <Head title="Admin errors" />
            <DashboardPageHeader
                eyebrow="Internal"
                title="Errors"
                description="Recent platform 5xx errors and source-specific failures will appear here with safe request context."
            />
            <AdminNav />
            <main className="p-5 lg:p-8">
                <EmptyState title="No error telemetry connected">
                    <CircleAlert className="mx-auto size-5 text-[#1248e8]" />
                    <p className="mt-2">
                        Provider integrations, structured logging and error
                        tracking are required before this page can report live
                        failures.
                    </p>
                </EmptyState>
            </main>
        </>
    );
}
AdminErrors.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin' },
        { title: 'Errors', href: '/admin/errors' },
    ],
};
