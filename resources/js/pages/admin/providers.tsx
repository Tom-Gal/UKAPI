import { Head } from '@inertiajs/react';
import { AdminNav } from '@/components/admin-nav';
import { DashboardPageHeader } from '@/components/dashboard-page-header';
type Provider = {
    code: string;
    scope: string;
    configuration: string;
    health: 'available' | 'unavailable' | 'stale' | 'unknown';
    health_detail: string;
    latency_ms: number | null;
    consecutive_failures: number;
    last_checked_at: string | null;
};
export default function AdminProviders({
    providers,
}: {
    providers: Provider[];
}) {
    return (
        <>
            <Head title="Admin providers" />
            <DashboardPageHeader
                eyebrow="Internal"
                title="Providers"
                description="Current upstream integrations and their configuration state. Credentials are never exposed here."
            />
            <AdminNav />
            <main className="p-5 lg:p-8">
                <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table className="w-full min-w-[840px] text-left text-sm">
                        <thead className="bg-slate-50 text-xs tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th className="px-5 py-3">Provider</th>
                                <th className="px-5 py-3">Coverage</th>
                                <th className="px-5 py-3">Configuration</th>
                                <th className="px-5 py-3">Observed health</th>
                                <th className="px-5 py-3">Latency</th>
                                <th className="px-5 py-3">Last checked</th>
                            </tr>
                        </thead>
                        <tbody>
                            {providers.map((provider) => (
                                <tr
                                    key={provider.code}
                                    className="border-t border-slate-100"
                                >
                                    <td className="px-5 py-4 font-mono text-xs">
                                        {provider.code}
                                    </td>
                                    <td className="px-5 py-4 text-slate-600">
                                        {provider.scope}
                                    </td>
                                    <td className="px-5 py-4">
                                        <span
                                            className={`rounded-full px-2 py-1 text-xs font-semibold ${provider.configuration === 'Credential missing' || provider.configuration === 'Snapshot missing' ? 'bg-rose-50 text-rose-800' : 'bg-emerald-50 text-emerald-800'}`}
                                        >
                                            {provider.configuration}
                                        </span>
                                    </td>
                                    <td className="px-5 py-4">
                                        <span
                                            className={`font-semibold ${provider.health === 'available' ? 'text-emerald-800' : provider.health === 'unavailable' ? 'text-rose-800' : 'text-amber-800'}`}
                                        >
                                            {provider.health_detail}
                                        </span>
                                    </td>
                                    <td className="px-5 py-4 font-mono text-xs text-slate-600">
                                        {provider.latency_ms === null
                                            ? '—'
                                            : `${provider.latency_ms} ms`}
                                        {provider.consecutive_failures > 0 && (
                                            <span className="mt-1 block font-sans font-semibold text-rose-700">
                                                {provider.consecutive_failures}{' '}
                                                consecutive failures
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-5 py-4 text-xs text-slate-600">
                                        {provider.last_checked_at
                                            ? new Date(
                                                  provider.last_checked_at,
                                              ).toLocaleString('en-GB')
                                            : 'Not measured'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </main>
        </>
    );
}
AdminProviders.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin' },
        { title: 'Providers', href: '/admin/providers' },
    ],
};
