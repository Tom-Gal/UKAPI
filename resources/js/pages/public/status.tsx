import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, CircleAlert, Clock3, History } from 'lucide-react';
import type { ComponentType } from 'react';
import { PageHero } from '@/pages/public/api-catalogue';

type ServiceState = 'available' | 'unavailable' | 'stale' | 'unknown';

type Service = {
    code: string;
    name: string;
    description: string;
    state: ServiceState;
    status_label: string;
    detail: string | null;
    impact: string | null;
    consecutive_failures: number;
    latency_ms: number | null;
    last_attempt_at: string | null;
    last_checked_at: string | null;
    last_success_at: string | null;
    source_freshness: 'fresh' | 'stale' | 'unknown' | 'not_applicable';
    source_date: string | null;
    reference_retrieved_at: string | null;
    reference_freshness: 'fresh' | 'stale' | 'unknown';
};

type Incident = {
    service: string;
    name: string;
    state: 'open' | 'resolved';
    title: string;
    detail: string;
    impact: string;
    started_at: string;
    last_observed_at: string;
    recovered_at: string | null;
    occurrences: number;
};

type OverallStatus = {
    title: string;
    detail: string;
    icon: ComponentType<{ className?: string }>;
    className: string;
};

export default function Status({
    services,
    incidents,
    measured_at,
}: {
    services: Service[];
    incidents: Incident[];
    measured_at: string;
}) {
    const openIncidents = incidents.filter(
        (incident) => incident.state === 'open',
    );
    const resolvedIncidents = incidents.filter(
        (incident) => incident.state === 'resolved',
    );
    const overall = overallStatus(services);
    const OverallIcon = overall.icon;

    return (
        <>
            <Head title="UK API service status">
                <meta
                    name="description"
                    content="Current UKApi.co.uk service availability and incident history."
                />
            </Head>
            <PageHero
                eyebrow="Service status"
                title="UKApi.co.uk service status"
                description="Latest automated checks for the API's core dependencies and each data source."
            />
            <div className="atlas-shell max-w-5xl pb-24">
                <section
                    className={`border border-slate-950 p-5 shadow-[5px_5px_0_#d7d2c3] sm:p-6 ${overall.className}`}
                    aria-labelledby="overall-status"
                >
                    <div className="flex items-start gap-4">
                        <OverallIcon className="mt-0.5 size-6 shrink-0" />
                        <div className="min-w-0">
                            <h2
                                id="overall-status"
                                className="text-xl font-semibold tracking-tight"
                            >
                                {overall.title}
                            </h2>
                            <p className="mt-1 text-sm leading-6">
                                {overall.detail}
                            </p>
                            <p className="mt-3 font-mono text-[10px] tracking-[0.08em] uppercase opacity-70">
                                Updated {timestamp(measured_at)}
                            </p>
                        </div>
                    </div>
                </section>

                <section className="mt-12" aria-labelledby="current-incidents">
                    <SectionHeading
                        id="current-incidents"
                        title="Current incidents"
                        detail={
                            openIncidents.length === 0
                                ? 'No active incidents.'
                                : `${openIncidents.length} active ${plural(openIncidents.length, 'incident')}.`
                        }
                    />
                    {openIncidents.length === 0 ? (
                        <div className="mt-4 flex items-center gap-3 border border-slate-300 bg-white px-5 py-4 text-sm text-slate-700">
                            <CheckCircle2 className="size-5 shrink-0 text-emerald-700" />
                            All monitored services are clear of recorded
                            incidents.
                        </div>
                    ) : (
                        <div className="mt-4 space-y-4">
                            {openIncidents.map((incident) => (
                                <IncidentCard
                                    key={`${incident.service}-${incident.started_at}`}
                                    incident={incident}
                                />
                            ))}
                        </div>
                    )}
                </section>

                <section className="mt-12" aria-labelledby="services">
                    <SectionHeading
                        id="services"
                        title="Services"
                        detail="Checks normally run every five minutes."
                    />
                    <div className="mt-4 divide-y divide-slate-300 border border-slate-950 bg-white shadow-[5px_5px_0_#d7d2c3]">
                        {services.map((service) => (
                            <StatusRow key={service.code} service={service} />
                        ))}
                    </div>
                </section>

                <section className="mt-12" aria-labelledby="recent-incidents">
                    <SectionHeading
                        id="recent-incidents"
                        title="Incident history"
                        detail="Resolved incidents from the past 14 days."
                    />
                    {resolvedIncidents.length === 0 ? (
                        <div className="mt-4 flex items-center gap-3 border border-slate-300 bg-white px-5 py-4 text-sm text-slate-700">
                            <History className="size-5 shrink-0 text-slate-500" />
                            No resolved incidents in this period.
                        </div>
                    ) : (
                        <div className="mt-4 divide-y divide-slate-300 border border-slate-950 bg-white">
                            {resolvedIncidents.map((incident) => (
                                <IncidentCard
                                    key={`${incident.service}-${incident.started_at}`}
                                    incident={incident}
                                    compact
                                />
                            ))}
                        </div>
                    )}
                </section>

                <div className="mt-8 border-l-2 border-[#2563c7] pl-4 text-sm leading-6 text-slate-600">
                    <p>
                        Coverage, licences and update schedules are listed in
                        the{' '}
                        <Link
                            href="/data-sources"
                            className="font-semibold text-slate-950 underline decoration-[#2563c7] underline-offset-4 hover:text-[#2563c7]"
                        >
                            data source register
                        </Link>
                        .
                    </p>
                    <p className="mt-2">
                        UKApi.co.uk is not an emergency alert service. Follow
                        official Environment Agency advice during a flood.
                    </p>
                </div>
            </div>
        </>
    );
}

function SectionHeading({
    id,
    title,
    detail,
}: {
    id: string;
    title: string;
    detail: string;
}) {
    return (
        <div className="flex flex-col gap-1 border-b border-slate-950 pb-3 sm:flex-row sm:items-end sm:justify-between sm:gap-5">
            <h2 id={id} className="text-2xl font-semibold tracking-tight">
                {title}
            </h2>
            <p className="text-sm text-slate-500">{detail}</p>
        </div>
    );
}

function StatusRow({ service }: { service: Service }) {
    const {
        icon: Icon,
        iconClassName,
        labelClassName,
    } = serviceStyle(service.state);

    return (
        <article className="p-5 sm:p-6">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex min-w-0 items-start gap-3">
                    <Icon
                        className={`mt-0.5 size-5 shrink-0 ${iconClassName}`}
                    />
                    <div>
                        <h3 className="font-semibold">{service.name}</h3>
                        <p className="mt-1 text-sm leading-5 text-slate-500">
                            {service.description}
                        </p>
                    </div>
                </div>
                <span
                    className={`shrink-0 font-mono text-[10px] font-bold tracking-[0.08em] uppercase ${labelClassName}`}
                >
                    {service.status_label}
                </span>
            </div>

            {service.detail && (
                <div
                    className={`mt-4 border-l-2 pl-4 text-sm leading-6 ${service.state === 'unavailable' ? 'border-rose-600 text-rose-950' : 'border-amber-500 text-amber-950'}`}
                >
                    <p className="font-semibold">{service.detail}</p>
                    {service.impact && <p>{service.impact}</p>}
                </div>
            )}

            <dl className="mt-4 flex flex-wrap gap-x-5 gap-y-2 font-mono text-[10px] leading-5 tracking-[0.04em] text-slate-500 uppercase">
                <div className="flex gap-1.5">
                    <dt>Last checked</dt>
                    <dd className="text-slate-700">
                        {timestamp(service.last_checked_at)}
                    </dd>
                </div>
                {service.latency_ms !== null && (
                    <div className="flex gap-1.5">
                        <dt>Check time</dt>
                        <dd className="text-slate-700">
                            {service.latency_ms} ms
                        </dd>
                    </div>
                )}
                {service.state === 'unavailable' && (
                    <>
                        <div className="flex gap-1.5">
                            <dt>Failed checks</dt>
                            <dd className="text-slate-700">
                                {service.consecutive_failures}
                            </dd>
                        </div>
                        <div className="flex gap-1.5">
                            <dt>Last operational</dt>
                            <dd className="text-slate-700">
                                {timestamp(service.last_success_at)}
                            </dd>
                        </div>
                    </>
                )}
            </dl>

            <SourceDate service={service} />
        </article>
    );
}

function SourceDate({ service }: { service: Service }) {
    if (service.source_date) {
        return (
            <p
                className={`mt-3 text-xs leading-5 ${service.source_freshness === 'stale' ? 'font-semibold text-amber-800' : 'text-slate-500'}`}
            >
                Latest published crime data: {sourceMonth(service.source_date)}
                {service.source_freshness === 'stale' &&
                    ' (older than expected)'}
            </p>
        );
    }

    if (service.reference_retrieved_at) {
        return (
            <p
                className={`mt-3 text-xs leading-5 ${service.reference_freshness === 'stale' ? 'font-semibold text-amber-800' : 'text-slate-500'}`}
            >
                SIC reference file retrieved{' '}
                {timestamp(service.reference_retrieved_at)}
                {service.reference_freshness === 'stale' &&
                    ' (older than expected)'}
            </p>
        );
    }

    return null;
}

function IncidentCard({
    incident,
    compact = false,
}: {
    incident: Incident;
    compact?: boolean;
}) {
    const resolved = incident.state === 'resolved';

    return (
        <article
            className={`${compact ? 'p-5 sm:p-6' : 'border border-rose-700 bg-rose-50 p-5 shadow-[4px_4px_0_#fecdd3] sm:p-6'}`}
        >
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p
                        className={`font-mono text-[10px] font-bold tracking-[0.1em] uppercase ${resolved ? 'text-emerald-700' : 'text-rose-700'}`}
                    >
                        {resolved ? 'Resolved' : 'Open incident'}
                    </p>
                    <h3 className="mt-1 text-lg font-semibold tracking-tight">
                        {incident.title}
                    </h3>
                </div>
                <p className="shrink-0 font-mono text-[10px] leading-5 tracking-[0.04em] text-slate-500 uppercase">
                    {resolved && incident.recovered_at
                        ? `Recovered ${timestamp(incident.recovered_at)}`
                        : `Last seen ${timestamp(incident.last_observed_at)}`}
                </p>
            </div>
            <p className="mt-4 text-sm leading-6 text-slate-800">
                {incident.detail} {incident.impact}
            </p>
            <p className="mt-3 font-mono text-[10px] leading-5 tracking-[0.04em] text-slate-500 uppercase">
                Started {timestamp(incident.started_at)} ·{' '}
                {incident.occurrences}{' '}
                {plural(incident.occurrences, 'failed observation')}
            </p>
        </article>
    );
}

function overallStatus(services: Service[]): OverallStatus {
    const unavailable = services.filter(
        (service) => service.state === 'unavailable',
    ).length;
    const delayed = services.filter(
        (service) => service.state === 'stale' || service.state === 'unknown',
    ).length;

    if (unavailable > 0) {
        return {
            title: `${unavailable} ${plural(unavailable, 'service')} disrupted`,
            detail:
                delayed > 0
                    ? `${delayed} more ${plural(delayed, 'service')} do not have a recent check.`
                    : 'Affected services and incident details are listed below.',
            icon: CircleAlert,
            className: 'bg-rose-100 text-rose-950',
        };
    }

    if (delayed > 0) {
        return {
            title: 'Status checks are delayed',
            detail: `${delayed} ${plural(delayed, 'service')} do not have a recent result. No outage is confirmed.`,
            icon: Clock3,
            className: 'bg-amber-100 text-amber-950',
        };
    }

    return {
        title: 'All monitored services are operational',
        detail: `The latest checks passed for all ${services.length} services.`,
        icon: CheckCircle2,
        className: 'bg-[#dbe4cd] text-[#122019]',
    };
}

function serviceStyle(state: ServiceState): {
    icon: ComponentType<{ className?: string }>;
    iconClassName: string;
    labelClassName: string;
} {
    if (state === 'available') {
        return {
            icon: CheckCircle2,
            iconClassName: 'text-emerald-700',
            labelClassName: 'text-emerald-700',
        };
    }

    if (state === 'unavailable') {
        return {
            icon: CircleAlert,
            iconClassName: 'text-rose-700',
            labelClassName: 'text-rose-700',
        };
    }

    return {
        icon: Clock3,
        iconClassName: 'text-amber-700',
        labelClassName: 'text-amber-700',
    };
}

function timestamp(value: string | null): string {
    if (!value) return 'Not recorded';

    return new Date(value)
        .toISOString()
        .replace('T', ' ')
        .replace('.000Z', ' UTC');
}

function sourceMonth(value: string): string {
    const [year, month] = value.split('-');
    const names = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
    ];

    return `${names[Number(month) - 1] ?? month} ${year}`;
}

function plural(count: number, noun: string): string {
    return count === 1 ? noun : `${noun}s`;
}
