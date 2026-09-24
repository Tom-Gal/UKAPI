import { Head } from '@inertiajs/react';
import { Flag } from 'lucide-react';
import { AdminNav } from '@/components/admin-nav';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';
export default function FeatureFlags() {
    return (
        <>
            <Head title="Admin feature flags" />
            <DashboardPageHeader
                eyebrow="Internal"
                title="Feature flags"
                description="Endpoint-family safety switches will be managed here once a persisted feature-flag service is introduced."
            />
            <AdminNav />
            <main className="p-5 lg:p-8">
                <EmptyState title="Feature flags are not configured">
                    <Flag className="mx-auto size-5 text-[#1248e8]" />
                    <p className="mt-2">
                        No endpoint or provider can be enabled from this
                        scaffold, so it cannot accidentally imply that an
                        integration is active.
                    </p>
                </EmptyState>
            </main>
        </>
    );
}
FeatureFlags.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin' },
        { title: 'Feature flags', href: '/admin/feature-flags' },
    ],
};
