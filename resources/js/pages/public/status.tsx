import { Head, Link } from '@inertiajs/react';
import { Activity, CheckCircle2, CircleAlert } from 'lucide-react';
import { PageHero } from '@/pages/public/api-catalogue';

type Service = {
    name: string;
    detail: string;
    state: 'available' | 'unavailable' | 'stale' | 'unknown';
    last_attempt_at: string | null;
    last_checked_at: string | null;
    last_success_at: string | null;
    source_freshness: 'fresh' | 'stale' | 'unknown' | 'not_applicable';
    source_date: string | null;
    source_detail: string;
    reference_retrieved_at: string | null;
    reference_freshness: 'fresh' | 'stale' | 'unknown';
};

export default function Status({
    services,
    measured_at,
}: {
    services: Service[];
    measured_at: string;
}) {
    return (
        <>
            <Head title="UK API service status">
                <meta
                    name="description"
                    content="UKApi.co.uk service and provider status."
                />
            </Head>
            <PageHero
                eyebrow="Service status"
                title="Measured availability across UKApi.co.uk."
                description="Scheduled checks of application dependencies and selected provider endpoints. Availability and source freshness are measured separately."
            />
            <div className="atlas-shell max-w-5xl pb-24">
                <div className="flex gap-3 border border-slate-950 bg-[#dbe4cd] p-5 text-sm leading-6">
                    <Activity className="mb-2 size-5 text-[#2563c7]" />
                    <div>
                        <p className="font-semibold">Scheduled observations</p>
                        <p>
                            Checks normally run every five minutes. A passing
                            check confirms only the sampled operation, not all
                            API requests, dashboard login, or dataset
                            completeness. Overdue measurements are marked stale.
                        </p>
                        <p>
                            Page generated: {timestamp(measured_at)}. Reload for
                            the latest observations.
                        </p>
                    </div>
                </div>
                <div className="mt-6 divide-y divide-slate-300 border border-slate-950 bg-white shadow-[5px_5px_0_#d7d2c3]">
                    {services.map((service) => (
                        <StatusRow key={service.name} service={service} />
                    ))}
                </div>
                <p className="mt-6 text-sm leading-6 text-slate-500">
                    Read the{' '}
                    <Link
                        href="/data-sources"
                        className="border-b border-[#2563c7] font-semibold text-slate-950 hover:text-[#2563c7]"
                    >
                        source coverage notes
                    </Link>{' '}
                    for coverage and freshness information.
                </p>
            </div>
        </>
    );
}

function StatusRow({ service }: { service: Service }) {
    const Icon = service.state === 'available' ? CheckCircle2 : CircleAlert;

    return (
        <div className="p-5 transition hover:bg-[#e4ece7]">
            <div className="flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                    <Icon className="size-4 text-slate-500" />
                    <span className="font-medium">{service.name}</span>
                </div>
                <span
                    className={`text-sm font-medium ${service.state === 'available' ? 'text-emerald-700' : 'text-amber-700'}`}
                >
                    {service.detail}
                </span>
            </div>
            <dl className="mt-4 grid gap-2 text-sm text-slate-600 sm:grid-cols-3">
                <div>
                    <dt>Last attempt</dt>
                    <dd>{timestamp(service.last_attempt_at)}</dd>
                </div>
                <div>
                    <dt>Last completed check</dt>
                    <dd>{timestamp(service.last_checked_at)}</dd>
                </div>
                <div>
                    <dt>Last successful check</dt>
                    <dd>{timestamp(service.last_success_at)}</dd>
                </div>
            </dl>
            <p className="mt-3 text-sm text-slate-600">
                Source freshness:{' '}
                <span className="font-medium">
                    {freshnessLabel(service.source_freshness)}
                </span>
                {service.source_date &&
                    ` (last observed data month: ${service.source_date})`}
                . {service.source_detail}
            </p>
            {service.reference_retrieved_at && (
                <p className="mt-2 text-sm text-slate-600">
                    Local reference retrieved:{' '}
                    {timestamp(service.reference_retrieved_at)}. Retrieval age:{' '}
                    {freshnessLabel(service.reference_freshness)}. Retrieval
                    time does not establish when the source was updated.
                </p>
            )}
        </div>
    );
}

function timestamp(value: string | null): string {
    if (!value) return 'No observation';
    // Fixed UTC formatting keeps server and browser rendering consistent.
    return new Date(value)
        .toISOString()
        .replace('T', ' ')
        .replace('.000Z', ' UTC');
}

function freshnessLabel(value: Service['source_freshness']): string {
    return {
        fresh: 'Within age threshold',
        stale: 'Older than age threshold',
        unknown: 'Unknown',
        not_applicable: 'Not applicable',
    }[value];
}
