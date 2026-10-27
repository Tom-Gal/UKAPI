import { Form, Head } from '@inertiajs/react';
import {
    Activity,
    BellRing,
    Database,
    Gauge,
    History,
    ServerCog,
    ShieldAlert,
} from 'lucide-react';
import { AdminNav } from '@/components/admin-nav';
import { DashboardPageHeader } from '@/components/dashboard-page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type EndpointMetric = {
    endpoint: string;
    label: string;
    requests: number;
    errors: number;
    error_rate: number;
    average_latency_ms: number;
    p95_latency_ms: number;
    max_latency_ms: number;
};
type ProviderMetric = {
    service: string;
    name: string;
    status: string;
    failure_reason: string | null;
    latency_ms: number | null;
    consecutive_failures: number;
    last_checked_at: string | null;
};
type Incident = {
    id: number;
    service: string;
    failure_reason: string;
    started_at: string;
    last_observed_at: string;
    recovered_at: string | null;
    occurrences: number;
    latest_latency_ms: number | null;
    acknowledged_at: string | null;
    acknowledged_by: string | null;
    acknowledgement_note: string | null;
};
type Hotspot = {
    user_id: number;
    name: string;
    email: string;
    rate_limit_rejections: number;
    quota_rejections: number;
    total_rejections: number;
};
type QueueHealth = {
    connection: string;
    pending: number;
    oldest_age_seconds: number | null;
    failed: number;
    recent_failures: Array<{
        uuid: string;
        connection: string;
        queue: string;
        failed_at: string;
    }>;
};
type NotificationHealth = {
    sent_24h: number;
    failed_24h: number;
    recent_failures: Array<{
        notification: string;
        channel: string;
        failure_type: string | null;
        occurred_at: string;
        user: string | null;
    }>;
};
type Snapshot = {
    dataset: string;
    scope: string;
    version: string;
    source: string;
    captured_at: string;
    source_updated_at: string | null;
    record_count: number;
    age_hours: number | null;
};
type AuditEvent = {
    id: number;
    actor: string;
    action: string;
    subject_type: string;
    subject_id: string;
    reason: string;
    created_at: string;
};

type Props = {
    endpointMetrics: EndpointMetric[];
    providerMetrics: ProviderMetric[];
    incidents: Incident[];
    hotspots: Hotspot[];
    queueHealth: QueueHealth;
    notificationHealth: NotificationHealth;
    snapshots: Snapshot[];
    auditEvents: AuditEvent[];
};

export default function AdminOperations(props: Props) {
    return (
        <>
            <Head title="Admin operations" />
            <DashboardPageHeader
                eyebrow="Internal"
                title="Operations"
                description="Privacy-conscious service telemetry, incident handling, delivery health, snapshot freshness, and audited support activity."
            />
            <AdminNav />
            <main className="space-y-6 p-5 lg:p-8">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Metric
                        icon={Activity}
                        label="Endpoint traffic"
                        value={props.endpointMetrics
                            .reduce((sum, row) => sum + row.requests, 0)
                            .toLocaleString()}
                        detail="Admitted requests in the last 7 UTC days"
                    />
                    <Metric
                        icon={ShieldAlert}
                        label="Open incidents"
                        value={props.incidents
                            .filter((incident) => !incident.recovered_at)
                            .length.toLocaleString()}
                        detail={`${props.incidents.filter((incident) => !incident.recovered_at && !incident.acknowledged_at).length} awaiting acknowledgement`}
                    />
                    <Metric
                        icon={ServerCog}
                        label="Queue"
                        value={`${props.queueHealth.pending} / ${props.queueHealth.failed}`}
                        detail="Pending / failed jobs"
                    />
                    <Metric
                        icon={BellRing}
                        label="Notifications"
                        value={`${props.notificationHealth.sent_24h} / ${props.notificationHealth.failed_24h}`}
                        detail="Sent / failed in the last 24 hours"
                    />
                </div>

                <OperationsTable
                    title="Endpoint errors and latency"
                    icon={Gauge}
                    description="Seven-day aggregates by stable endpoint code. Percentiles use bounded latency buckets."
                    empty="No endpoint metrics have been recorded yet."
                    headers={[
                        'Endpoint',
                        'Requests',
                        'Errors',
                        'Average',
                        'P95',
                        'Maximum',
                    ]}
                >
                    {props.endpointMetrics.map((metric) => (
                        <tr key={metric.endpoint}>
                            <Cell>
                                <p className="font-medium">{metric.label}</p>
                                <p className="font-mono text-xs text-slate-500">
                                    {metric.endpoint}
                                </p>
                            </Cell>
                            <Cell mono>{metric.requests.toLocaleString()}</Cell>
                            <Cell mono>
                                {metric.errors.toLocaleString()} (
                                {metric.error_rate}
                                %)
                            </Cell>
                            <Cell mono>{metric.average_latency_ms} ms</Cell>
                            <Cell mono>≤ {metric.p95_latency_ms} ms</Cell>
                            <Cell mono>{metric.max_latency_ms} ms</Cell>
                        </tr>
                    ))}
                </OperationsTable>

                <OperationsTable
                    title="Provider probes"
                    icon={Activity}
                    description="Latest bounded probe result. Credentials and provider response bodies are never shown."
                    empty="No provider probes have run yet."
                    headers={[
                        'Provider',
                        'State',
                        'Latency',
                        'Failures',
                        'Last checked',
                    ]}
                >
                    {props.providerMetrics.map((provider) => (
                        <tr key={provider.service}>
                            <Cell>
                                <p className="font-medium">{provider.name}</p>
                                <p className="font-mono text-xs text-slate-500">
                                    {provider.service}
                                </p>
                            </Cell>
                            <Cell>
                                <Status value={provider.status} />
                                {provider.failure_reason && (
                                    <p className="mt-1 font-mono text-xs text-rose-700">
                                        {provider.failure_reason}
                                    </p>
                                )}
                            </Cell>
                            <Cell mono>
                                {provider.latency_ms === null
                                    ? '—'
                                    : `${provider.latency_ms} ms`}
                            </Cell>
                            <Cell mono>{provider.consecutive_failures}</Cell>
                            <Cell>
                                {provider.last_checked_at
                                    ? formatDateTime(provider.last_checked_at)
                                    : 'Not measured'}
                            </Cell>
                        </tr>
                    ))}
                </OperationsTable>

                <section className="border border-slate-200 bg-white">
                    <SectionHeader
                        icon={ShieldAlert}
                        title="Health incidents"
                        description="Failure episodes remain available after recovery. Acknowledgements record the operator and note."
                    />
                    {props.incidents.length === 0 ? (
                        <Empty text="No health incidents recorded." />
                    ) : (
                        <div className="divide-y divide-slate-100">
                            {props.incidents.map((incident) => (
                                <article
                                    key={incident.id}
                                    className="grid gap-4 p-5 lg:grid-cols-[1fr_1fr]"
                                >
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-semibold">
                                                {incident.service}
                                            </p>
                                            <Status
                                                value={
                                                    incident.recovered_at
                                                        ? 'recovered'
                                                        : 'open'
                                                }
                                            />
                                        </div>
                                        <p className="mt-2 font-mono text-xs text-slate-600">
                                            {incident.failure_reason} ·{' '}
                                            {incident.occurrences} observations
                                        </p>
                                        <p className="mt-2 text-xs text-slate-500">
                                            Started{' '}
                                            {formatDateTime(
                                                incident.started_at,
                                            )}
                                            ; last observed{' '}
                                            {formatDateTime(
                                                incident.last_observed_at,
                                            )}
                                        </p>
                                        {incident.acknowledged_at && (
                                            <div className="mt-3 border-l-2 border-[#2563c7] pl-3 text-sm text-slate-700">
                                                <p>
                                                    {
                                                        incident.acknowledgement_note
                                                    }
                                                </p>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    {incident.acknowledged_by} ·{' '}
                                                    {formatDateTime(
                                                        incident.acknowledged_at,
                                                    )}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                    <Form
                                        action={`/admin/incidents/${incident.id}/acknowledge`}
                                        method="post"
                                        className="flex flex-col gap-2 sm:flex-row sm:items-start"
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <div className="flex-1">
                                                    <Input
                                                        name="note"
                                                        required
                                                        minLength={3}
                                                        maxLength={500}
                                                        defaultValue={
                                                            incident.acknowledgement_note ??
                                                            ''
                                                        }
                                                        aria-label={`Acknowledgement note for ${incident.service}`}
                                                        placeholder="Investigation or response note"
                                                    />
                                                    {errors.note && (
                                                        <p className="mt-1 text-xs text-red-600">
                                                            {errors.note}
                                                        </p>
                                                    )}
                                                </div>
                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                    variant="outline"
                                                >
                                                    Save acknowledgement
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                </article>
                            ))}
                        </div>
                    )}
                </section>

                <div className="grid gap-6 xl:grid-cols-2">
                    <SimpleList
                        icon={ShieldAlert}
                        title="Quota and rate-limit hotspots"
                        empty="No account rejections this month."
                    >
                        {props.hotspots.map((hotspot) => (
                            <div
                                key={hotspot.user_id}
                                className="flex justify-between gap-4 border-t border-slate-100 px-5 py-3 text-sm"
                            >
                                <div>
                                    <p className="font-medium">
                                        {hotspot.name}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {hotspot.email}
                                    </p>
                                </div>
                                <p className="text-right font-mono text-xs text-slate-600">
                                    {hotspot.rate_limit_rejections} rate
                                    <br />
                                    {hotspot.quota_rejections} quota
                                </p>
                            </div>
                        ))}
                    </SimpleList>
                    <SimpleList
                        icon={ServerCog}
                        title="Recent failed jobs"
                        empty="No failed jobs."
                    >
                        {props.queueHealth.recent_failures.map((failure) => (
                            <div
                                key={failure.uuid}
                                className="border-t border-slate-100 px-5 py-3 text-sm"
                            >
                                <p className="font-medium">
                                    {failure.connection} / {failure.queue}
                                </p>
                                <p className="mt-1 font-mono text-xs text-slate-500">
                                    {failure.uuid} ·{' '}
                                    {formatDateTime(failure.failed_at)}
                                </p>
                            </div>
                        ))}
                    </SimpleList>
                    <SimpleList
                        icon={BellRing}
                        title="Notification delivery failures"
                        empty="No notification failures recorded."
                    >
                        {props.notificationHealth.recent_failures.map(
                            (failure, index) => (
                                <div
                                    key={`${failure.occurred_at}-${index}`}
                                    className="border-t border-slate-100 px-5 py-3 text-sm"
                                >
                                    <p className="font-medium">
                                        {failure.notification} ·{' '}
                                        {failure.channel}
                                    </p>
                                    <p className="mt-1 text-xs text-slate-500">
                                        {failure.user ?? 'On-demand recipient'}{' '}
                                        ·{' '}
                                        {failure.failure_type ??
                                            'Unknown failure'}{' '}
                                        · {formatDateTime(failure.occurred_at)}
                                    </p>
                                </div>
                            ),
                        )}
                    </SimpleList>
                    <SimpleList
                        icon={History}
                        title="Admin audit trail"
                        empty="No support actions recorded."
                    >
                        {props.auditEvents.map((event) => (
                            <div
                                key={event.id}
                                className="border-t border-slate-100 px-5 py-3 text-sm"
                            >
                                <p className="font-medium">
                                    {formatAction(event.action)} · {event.actor}
                                </p>
                                <p className="mt-1 text-xs leading-5 text-slate-500">
                                    {event.subject_type} #{event.subject_id} ·{' '}
                                    {event.reason} ·{' '}
                                    {formatDateTime(event.created_at)}
                                </p>
                            </div>
                        ))}
                    </SimpleList>
                </div>

                <OperationsTable
                    title="Active source snapshots"
                    icon={Database}
                    description="Current immutable snapshot heads, versions, source dates, and capture age."
                    empty="No source snapshots are active."
                    headers={[
                        'Dataset / scope',
                        'Version',
                        'Source',
                        'Records',
                        'Captured',
                        'Source updated',
                    ]}
                >
                    {props.snapshots.map((snapshot) => (
                        <tr key={`${snapshot.dataset}-${snapshot.scope}`}>
                            <Cell>
                                <p className="font-medium">
                                    {snapshot.dataset}
                                </p>
                                <p className="font-mono text-xs text-slate-500">
                                    {snapshot.scope}
                                </p>
                            </Cell>
                            <Cell mono>{snapshot.version}</Cell>
                            <Cell>{snapshot.source}</Cell>
                            <Cell mono>
                                {snapshot.record_count.toLocaleString()}
                            </Cell>
                            <Cell>
                                {formatDateTime(snapshot.captured_at)}
                                {snapshot.age_hours !== null && (
                                    <span className="block text-xs text-slate-500">
                                        {Math.round(snapshot.age_hours)} hours
                                        ago
                                    </span>
                                )}
                            </Cell>
                            <Cell>
                                {snapshot.source_updated_at
                                    ? formatDateTime(snapshot.source_updated_at)
                                    : 'Not supplied'}
                            </Cell>
                        </tr>
                    ))}
                </OperationsTable>
            </main>
        </>
    );
}

function Metric({
    icon: Icon,
    label,
    value,
    detail,
}: {
    icon: typeof Activity;
    label: string;
    value: string;
    detail: string;
}) {
    return (
        <section className="border border-slate-200 bg-white p-5">
            <p className="flex items-center gap-2 text-xs font-bold tracking-wide text-slate-500 uppercase">
                <Icon className="size-4" /> {label}
            </p>
            <p className="mt-3 font-mono text-2xl font-semibold">{value}</p>
            <p className="mt-2 text-xs text-slate-500">{detail}</p>
        </section>
    );
}

function OperationsTable({
    title,
    icon: Icon,
    description,
    empty,
    headers,
    children,
}: {
    title: string;
    icon: typeof Activity;
    description: string;
    empty: string;
    headers: string[];
    children: React.ReactNode;
}) {
    const hasRows = Array.isArray(children)
        ? children.length > 0
        : Boolean(children);
    return (
        <section className="border border-slate-200 bg-white">
            <SectionHeader
                icon={Icon}
                title={title}
                description={description}
            />
            {!hasRows ? (
                <Empty text={empty} />
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[760px] text-left text-sm">
                        <thead className="bg-slate-50 text-xs tracking-wide text-slate-500 uppercase">
                            <tr>
                                {headers.map((header) => (
                                    <th key={header} className="px-5 py-3">
                                        {header}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {children}
                        </tbody>
                    </table>
                </div>
            )}
        </section>
    );
}

function SimpleList({
    icon: Icon,
    title,
    empty,
    children,
}: {
    icon: typeof Activity;
    title: string;
    empty: string;
    children: React.ReactNode;
}) {
    const hasRows = Array.isArray(children)
        ? children.length > 0
        : Boolean(children);
    return (
        <section className="border border-slate-200 bg-white">
            <SectionHeader icon={Icon} title={title} />
            {hasRows ? children : <Empty text={empty} />}
        </section>
    );
}

function SectionHeader({
    icon: Icon,
    title,
    description,
}: {
    icon: typeof Activity;
    title: string;
    description?: string;
}) {
    return (
        <div className="border-b border-slate-200 px-5 py-4">
            <h2 className="flex items-center gap-2 font-semibold">
                <Icon className="size-4" /> {title}
            </h2>
            {description && (
                <p className="mt-1 text-xs leading-5 text-slate-500">
                    {description}
                </p>
            )}
        </div>
    );
}

function Cell({
    children,
    mono = false,
}: {
    children: React.ReactNode;
    mono?: boolean;
}) {
    return (
        <td
            className={`px-5 py-4 text-slate-700 ${mono ? 'font-mono text-xs' : ''}`}
        >
            {children}
        </td>
    );
}

function Status({ value }: { value: string }) {
    const healthy = ['available', 'recovered'].includes(value);
    const failed = ['unavailable', 'open'].includes(value);
    return (
        <span
            className={`px-2 py-1 text-xs font-semibold ${healthy ? 'bg-emerald-50 text-emerald-800' : failed ? 'bg-rose-50 text-rose-800' : 'bg-amber-50 text-amber-800'}`}
        >
            {value}
        </span>
    );
}

function Empty({ text }: { text: string }) {
    return <p className="px-5 py-6 text-sm text-slate-500">{text}</p>;
}

function formatDateTime(value: string) {
    return new Date(value).toLocaleString('en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function formatAction(value: string) {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

AdminOperations.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin' },
        { title: 'Operations', href: '/admin/operations' },
    ],
};
