import { Head } from '@inertiajs/react';
import { ScrollText } from 'lucide-react';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';

export default function RequestLogs() {
    return (
        <>
            <Head title="Request logs" />
            <DashboardPageHeader
                eyebrow="Observability"
                title="Request logs"
                description="Recent request history will show response status, latency and cache state without retaining unnecessary sensitive query content."
            />
            <main className="p-5 lg:p-8">
                <EmptyState title="No request logs yet">
                    <ScrollText className="mx-auto size-5 text-[#1248e8]" />
                    <p className="mt-2">
                        The API gateway is not active in this phase. When
                        enabled, request logs will have a short retention window
                        and never expose API secrets.
                    </p>
                </EmptyState>
            </main>
        </>
    );
}
RequestLogs.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Request logs', href: '/request-logs' },
    ],
};
