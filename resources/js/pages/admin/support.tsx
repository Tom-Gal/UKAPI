import { Form, Head } from '@inertiajs/react';
import { FileSearch, LockKeyhole, Search, Ticket, Unlock } from 'lucide-react';
import { AdminNav } from '@/components/admin-nav';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type RequestLog = {
    id: number;
    request_id: string;
    method: string;
    route_path: string | null;
    route_name: string | null;
    endpoint: string;
    status: number;
    duration_ms: number;
    error_code: string | null;
    error_reason: string | null;
    error_message: string | null;
    user: { name: string; email: string } | null;
    api_key_prefix: string | null;
    created_at: string | null;
};

type Ticket = {
    id: number;
    request_id: string | null;
    title: string;
    notes: string;
    status: 'open' | 'closed';
    opened_by: string;
    closed_by: string | null;
    closed_at: string | null;
    resolution: string | null;
    created_at: string | null;
};

type Props = {
    requestId: string | null;
    ticketStatus: 'open' | 'closed' | 'all';
    status: string | null;
    openTicketsCount: number;
    closedTicketsCount: number;
    requestLogs: RequestLog[];
    tickets: Ticket[];
};

export default function AdminSupport({
    requestId,
    ticketStatus,
    status,
    openTicketsCount,
    closedTicketsCount,
    requestLogs,
    tickets,
}: Props) {
    return (
        <>
            <Head title="Admin support" />
            <DashboardPageHeader
                eyebrow="Internal"
                title="Support desk"
                description="Search safe API request telemetry, investigate complaints, and keep internal tickets moving. This page is never customer-facing."
                action={
                    <div className="flex gap-5 text-right font-mono text-xs text-slate-600">
                        <div>
                            <p className="text-2xl font-semibold text-slate-950">
                                {openTicketsCount}
                            </p>
                            <p>Open tickets</p>
                        </div>
                        <div>
                            <p className="text-2xl font-semibold text-slate-950">
                                {closedTicketsCount}
                            </p>
                            <p>Closed tickets</p>
                        </div>
                    </div>
                }
            />
            <AdminNav />
            <main className="space-y-6 p-5 lg:p-8">
                {status && (
                    <div className="border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                        {status}
                    </div>
                )}

                <section className="border border-slate-200 bg-white p-5">
                    <div className="flex items-start gap-3">
                        <Search className="mt-0.5 size-5 text-[#2563c7]" />
                        <div>
                            <h2 className="font-semibold">Find a request</h2>
                            <p className="mt-1 text-sm leading-6 text-slate-600">
                                Search by the exact request ID supplied to a
                                customer, or use a leading part of it.
                            </p>
                        </div>
                    </div>
                    <form
                        action="/admin/support"
                        method="get"
                        className="mt-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_180px_auto] md:items-end"
                    >
                        <label className="block">
                            <span className="mb-1 block text-xs font-bold tracking-wide text-slate-500 uppercase">
                                Request ID
                            </span>
                            <Input
                                name="request_id"
                                defaultValue={requestId ?? ''}
                                placeholder="req_01…"
                                className="font-mono"
                                spellCheck={false}
                            />
                        </label>
                        <label className="block">
                            <span className="mb-1 block text-xs font-bold tracking-wide text-slate-500 uppercase">
                                Tickets
                            </span>
                            <select
                                name="status"
                                defaultValue={ticketStatus}
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="open">Open tickets</option>
                                <option value="closed">Closed tickets</option>
                                <option value="all">All tickets</option>
                            </select>
                        </label>
                        <Button type="submit">
                            <FileSearch className="size-4" />
                            Search
                        </Button>
                    </form>
                    <p className="mt-3 text-xs leading-5 text-slate-500">
                        Stored telemetry excludes query values, request bodies,
                        bearer secrets, provider responses, and exception
                        bodies.
                    </p>
                </section>

                <section className="border border-slate-200 bg-white">
                    <SectionHeading
                        icon={FileSearch}
                        title="Request logs"
                        description={
                            requestId
                                ? `Matching request IDs beginning with ${requestId}.`
                                : 'Enter a request ID to load request-level evidence.'
                        }
                    />
                    {requestLogs.length === 0 ? (
                        <EmptyState
                            title={
                                requestId
                                    ? 'No matching request logs'
                                    : 'No request selected'
                            }
                        >
                            <p>
                                {requestId
                                    ? 'Check the ID and try again. Request logs may predate this support desk or have expired from retention.'
                                    : 'The request ID is included in every API response and is safe to share with support.'}
                            </p>
                        </EmptyState>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[900px] text-left text-sm">
                                <thead className="border-b border-slate-200 bg-slate-50 text-xs font-bold tracking-wide text-slate-500 uppercase">
                                    <tr>
                                        <th className="px-5 py-3">When</th>
                                        <th className="px-5 py-3">Request</th>
                                        <th className="px-5 py-3">Route</th>
                                        <th className="px-5 py-3">Account</th>
                                        <th className="px-5 py-3">Outcome</th>
                                        <th className="px-5 py-3 text-right">
                                            Action
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {requestLogs.map((log) => (
                                        <tr key={log.id} className="align-top">
                                            <td className="px-5 py-4 text-xs whitespace-nowrap text-slate-600">
                                                {formatDateTime(log.created_at)}
                                                <span className="mt-1 block font-mono">
                                                    {log.duration_ms} ms
                                                </span>
                                            </td>
                                            <td className="px-5 py-4">
                                                <code className="font-mono text-xs text-slate-900">
                                                    {log.request_id}
                                                </code>
                                                <span className="mt-1 block font-mono text-xs text-slate-500">
                                                    {log.method} · {log.status}
                                                </span>
                                            </td>
                                            <td className="px-5 py-4">
                                                <p className="font-medium text-slate-900">
                                                    {log.endpoint}
                                                </p>
                                                <p className="mt-1 font-mono text-xs text-slate-500">
                                                    {log.route_path ??
                                                        log.route_name ??
                                                        'Unmatched route'}
                                                </p>
                                            </td>
                                            <td className="px-5 py-4 text-xs text-slate-600">
                                                {log.user ? (
                                                    <>
                                                        <p className="font-medium text-slate-900">
                                                            {log.user.name}
                                                        </p>
                                                        <p>{log.user.email}</p>
                                                    </>
                                                ) : (
                                                    <span>
                                                        Unauthenticated /
                                                        unknown
                                                    </span>
                                                )}
                                                {log.api_key_prefix && (
                                                    <p className="mt-1 font-mono">
                                                        {log.api_key_prefix}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="max-w-[260px] px-5 py-4">
                                                <StatusBadge
                                                    status={log.status}
                                                />
                                                {log.error_code && (
                                                    <p className="mt-2 font-mono text-xs text-rose-700">
                                                        {log.error_code} ·{' '}
                                                        {log.error_reason}
                                                    </p>
                                                )}
                                                {log.error_message && (
                                                    <p className="mt-1 text-xs leading-5 text-slate-600">
                                                        {log.error_message}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-5 py-4 text-right">
                                                <a
                                                    href={`/admin/support?request_id=${encodeURIComponent(log.request_id)}#new-ticket`}
                                                    className="text-xs font-bold text-[#174ea6] underline underline-offset-4 hover:text-[#2563c7]"
                                                >
                                                    Open ticket
                                                </a>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>

                <section
                    id="new-ticket"
                    className="border border-slate-200 bg-white p-5"
                >
                    <SectionHeading
                        icon={Ticket}
                        title="Open an internal ticket"
                        description="Record the complaint or query and the internal context needed to resolve it. Do not paste secrets into notes."
                    />
                    <Form
                        action="/admin/support/tickets"
                        method="post"
                        className="mt-5 space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field
                                        label="Request ID"
                                        error={errors.request_id}
                                    >
                                        <Input
                                            name="request_id"
                                            defaultValue={requestId ?? ''}
                                            placeholder="Optional, req_01…"
                                            className="font-mono"
                                            spellCheck={false}
                                        />
                                    </Field>
                                    <Field
                                        label="Short title"
                                        error={errors.title}
                                    >
                                        <Input
                                            name="title"
                                            required
                                            maxLength={160}
                                            placeholder="Customer reports a postcode lookup failure"
                                        />
                                    </Field>
                                </div>
                                <Field
                                    label="Internal notes"
                                    error={errors.notes}
                                >
                                    <textarea
                                        name="notes"
                                        required
                                        minLength={3}
                                        maxLength={5000}
                                        rows={4}
                                        placeholder="What was reported, what you found, and what needs doing next."
                                        className="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    />
                                </Field>
                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing}>
                                        <Unlock className="size-4" />
                                        Open ticket
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </section>

                <section className="border border-slate-200 bg-white">
                    <SectionHeading
                        icon={ticketStatus === 'closed' ? LockKeyhole : Unlock}
                        title={
                            ticketStatus === 'closed'
                                ? 'Closed tickets'
                                : 'Open tickets'
                        }
                        description="Internal notes and resolutions are visible to administrators only."
                    />
                    {tickets.length === 0 ? (
                        <EmptyState title="No tickets in this view">
                            <p>
                                Open a ticket from a request log or create one
                                for a general account query.
                            </p>
                        </EmptyState>
                    ) : (
                        <div className="divide-y divide-slate-100">
                            {tickets.map((ticket) => (
                                <TicketRow key={ticket.id} ticket={ticket} />
                            ))}
                        </div>
                    )}
                </section>
            </main>
        </>
    );
}

function TicketRow({ ticket }: { ticket: Ticket }) {
    return (
        <article className="grid gap-5 p-5 lg:grid-cols-[1fr_1.2fr]">
            <div>
                <div className="flex flex-wrap items-center gap-2">
                    <h3 className="font-semibold text-slate-950">
                        #{ticket.id} · {ticket.title}
                    </h3>
                    <StatusBadge
                        status={ticket.status === 'open' ? 200 : 200}
                        label={ticket.status}
                    />
                </div>
                <p className="mt-2 font-mono text-xs text-slate-500">
                    {ticket.request_id ?? 'No request ID'} · Opened{' '}
                    {formatDateTime(ticket.created_at)} by {ticket.opened_by}
                </p>
                <p className="mt-3 text-sm leading-6 whitespace-pre-wrap text-slate-700">
                    {ticket.notes}
                </p>
            </div>
            <div className="border-l border-slate-200 pl-0 lg:pl-5">
                {ticket.status === 'open' ? (
                    <Form
                        action={`/admin/support/tickets/${ticket.id}/close`}
                        method="post"
                        className="space-y-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <label className="block">
                                    <span className="mb-1 block text-xs font-bold tracking-wide text-slate-500 uppercase">
                                        Resolution
                                    </span>
                                    <textarea
                                        name="resolution"
                                        required
                                        minLength={3}
                                        maxLength={5000}
                                        rows={3}
                                        placeholder="What resolved this ticket or what was communicated internally?"
                                        className="flex min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    />
                                    {errors.resolution && (
                                        <p className="mt-1 text-xs text-red-600">
                                            {errors.resolution}
                                        </p>
                                    )}
                                </label>
                                <div className="flex justify-end">
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        <LockKeyhole className="size-4" />
                                        Close ticket
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                ) : (
                    <>
                        <p className="text-xs font-bold tracking-wide text-slate-500 uppercase">
                            Resolution
                        </p>
                        <p className="mt-2 text-sm leading-6 whitespace-pre-wrap text-slate-700">
                            {ticket.resolution}
                        </p>
                        <p className="mt-3 text-xs text-slate-500">
                            Closed {formatDateTime(ticket.closed_at)} by{' '}
                            {ticket.closed_by ?? 'Former administrator'}
                        </p>
                    </>
                )}
            </div>
        </article>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <label className="block">
            <span className="mb-1 block text-xs font-bold tracking-wide text-slate-500 uppercase">
                {label}
            </span>
            {children}
            {error && (
                <span className="mt-1 block text-xs text-red-600">{error}</span>
            )}
        </label>
    );
}

function SectionHeading({
    icon: Icon,
    title,
    description,
}: {
    icon: typeof FileSearch;
    title: string;
    description: string;
}) {
    return (
        <div className="border-b border-slate-200 px-5 py-4">
            <h2 className="flex items-center gap-2 font-semibold text-slate-950">
                <Icon className="size-4" /> {title}
            </h2>
            <p className="mt-1 text-xs leading-5 text-slate-500">
                {description}
            </p>
        </div>
    );
}

function StatusBadge({ status, label }: { status: number; label?: string }) {
    const successful = status < 400;
    return (
        <span
            className={`px-2 py-1 text-xs font-semibold ${successful ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800'}`}
        >
            {label ?? status}
        </span>
    );
}

function formatDateTime(value: string | null) {
    if (!value) {
        return 'Unknown time';
    }

    return new Date(value).toLocaleString('en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

AdminSupport.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin' },
        { title: 'Support', href: '/admin/support' },
    ],
};
