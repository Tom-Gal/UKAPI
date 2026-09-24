import { Head, Link } from '@inertiajs/react';
import { Activity, CircleAlert, Clock3 } from 'lucide-react';
import { PageHero } from '@/pages/public/api-catalogue';

export default function Status() {
    return (
        <>
            <Head title="Service status">
                <meta
                    name="description"
                    content="UKAPI.io service and provider status."
                />
            </Head>
            <PageHero
                eyebrow="Service status"
                title="Transparent about what the platform can currently serve."
                description="Independent production monitoring will be connected before launch. This pre-launch status page intentionally does not invent availability data."
            />
            <div className="mx-auto max-w-4xl px-5 pb-20 lg:px-8">
                <div className="rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm leading-6 text-blue-950">
                    <Activity className="mb-2 size-5 text-[#1248e8]" />
                    <strong>Pre-launch environment</strong>
                    <br />
                    No public API endpoint or provider health data is available
                    yet. Production status monitoring will be independent of the
                    application itself.
                </div>
                <div className="mt-6 divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white">
                    <StatusRow
                        icon={Clock3}
                        name="Public API"
                        detail="Not launched"
                    />
                    <StatusRow
                        icon={Clock3}
                        name="Dashboard"
                        detail="Available for account setup"
                    />
                    <StatusRow
                        icon={CircleAlert}
                        name="Provider health"
                        detail="Not configured"
                    />
                </div>
                <p className="mt-6 text-sm leading-6 text-slate-500">
                    During launch, source-specific outages will degrade only the
                    affected endpoint family where safe. Read the{' '}
                    <Link
                        href="/data-sources"
                        className="font-semibold text-[#1248e8]"
                    >
                        source coverage notes
                    </Link>{' '}
                    before relying on data for a time-sensitive decision.
                </p>
            </div>
        </>
    );
}

function StatusRow({
    icon: Icon,
    name,
    detail,
}: {
    icon: typeof Activity;
    name: string;
    detail: string;
}) {
    return (
        <div className="flex items-center justify-between gap-4 p-5">
            <div className="flex items-center gap-3">
                <Icon className="size-4 text-slate-500" />
                <span className="font-medium">{name}</span>
            </div>
            <span className="text-sm text-slate-500">{detail}</span>
        </div>
    );
}
