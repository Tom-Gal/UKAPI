import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    Copy,
    Download,
    History,
    RotateCw,
    Settings2,
    ShieldAlert,
} from 'lucide-react';
import { useState } from 'react';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ApiKey = {
    id: number;
    name: string;
    prefix: string;
    environment: 'live' | 'test';
    status: string;
    scopes: string[] | null;
    ip_allow_list: string[];
    last_used_endpoint_family: string | null;
    last_used_endpoint_family_label: string | null;
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string;
    revoked_at: string | null;
};

type AuditEvent = {
    id: number;
    action: string;
    request_id: string | null;
    created_at: string | null;
    key_name: string;
    key_prefix: string;
    details: Record<string, unknown> | null;
};

type ScopeOption = { key: string; label: string };

type Props = {
    apiKeys: ApiKey[];
    auditEvents: AuditEvent[];
    scopeOptions: ScopeOption[];
    canUseIpAllowLists: boolean;
    planLabel: string;
};

type CreatedKey = {
    name: string;
    token: string;
    activity: 'created' | 'rotated';
    overlap_hours?: number;
};

export default function ApiKeys({
    apiKeys,
    auditEvents,
    scopeOptions,
    canUseIpAllowLists,
    planLabel,
}: Props) {
    const { flash } = usePage<{
        flash: { api_key_created?: CreatedKey };
    }>().props;
    const newKey = flash.api_key_created;
    const command = newKey
        ? `curl "https://api.ukapi.co.uk/v1/postcodes/BL2%206XX" -H "Authorization: Bearer ${newKey.token}" -H "Accept: application/json"`
        : '';

    return (
        <>
            <Head title="API keys" />
            <DashboardPageHeader
                eyebrow="Credentials"
                title="API keys"
                description="Create, scope, rotate, and audit credentials. Secret values are shown exactly once and are never included in activity records."
            />
            <main className="space-y-6 p-5 lg:p-8">
                {newKey && <SecretNotice newKey={newKey} command={command} />}

                <div className="grid gap-6 xl:grid-cols-[.75fr_1.25fr]">
                    <section className="border border-slate-200 bg-white p-6">
                        <h2 className="font-semibold">Create an API key</h2>
                        <p className="mt-2 text-sm leading-6 text-slate-600">
                            Test and live keys use the same API data, quotas,
                            and billing today. Their prefixes help you keep
                            deployments separate; test is not a sandbox.
                        </p>
                        <Form
                            action="/api-keys"
                            method="post"
                            className="mt-6 space-y-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Key name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            maxLength={80}
                                            placeholder="Production server"
                                        />
                                        <FieldError value={errors.name} />
                                    </div>
                                    <fieldset className="grid gap-2">
                                        <legend className="text-sm font-medium">
                                            Environment label
                                        </legend>
                                        <label className="flex items-start gap-2 text-sm">
                                            <input
                                                type="radio"
                                                name="environment"
                                                value="test"
                                                defaultChecked
                                                className="mt-1"
                                            />
                                            <span>
                                                Test, starts with{' '}
                                                <code>uk_test_</code>
                                            </span>
                                        </label>
                                        <label className="flex items-start gap-2 text-sm">
                                            <input
                                                type="radio"
                                                name="environment"
                                                value="live"
                                                className="mt-1"
                                            />
                                            <span>
                                                Live, starts with{' '}
                                                <code>uk_live_</code>
                                            </span>
                                        </label>
                                        <FieldError
                                            value={errors.environment}
                                        />
                                    </fieldset>
                                    <ScopeFields
                                        scopeOptions={scopeOptions}
                                        error={errors.scopes}
                                    />
                                    <IpAllowListField
                                        canUse={canUseIpAllowLists}
                                        planLabel={planLabel}
                                        error={errors.ip_allow_list}
                                    />
                                    <Button
                                        disabled={processing}
                                        className="w-full bg-[#13231a] hover:bg-[#2563c7]"
                                    >
                                        {processing
                                            ? 'Creating key…'
                                            : 'Create API key'}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>

                    <section className="border border-slate-200 bg-white">
                        <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                            <h2 className="font-semibold">Your keys</h2>
                            <span className="font-mono text-xs text-slate-500">
                                {apiKeys.length} total
                            </span>
                        </div>
                        {apiKeys.length === 0 ? (
                            <EmptyState title="No keys yet">
                                <p>
                                    Create a test key to prepare your
                                    integration.
                                </p>
                            </EmptyState>
                        ) : (
                            <div className="divide-y divide-slate-200">
                                {apiKeys.map((key) => (
                                    <KeyPanel
                                        key={key.id}
                                        apiKey={key}
                                        scopeOptions={scopeOptions}
                                        canUseIpAllowLists={canUseIpAllowLists}
                                        planLabel={planLabel}
                                    />
                                ))}
                            </div>
                        )}
                    </section>
                </div>

                <section className="border border-slate-200 bg-white">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                        <div>
                            <h2 className="flex items-center gap-2 font-semibold">
                                <History className="size-4" /> Security activity
                            </h2>
                            <p className="mt-1 text-xs text-slate-500">
                                Key lifecycle and policy events only. No request
                                contents, bearer secrets, or response data.
                            </p>
                        </div>
                        <a
                            href="/api-keys/security-export.csv"
                            className="inline-flex items-center gap-2 border border-slate-300 px-3 py-2 text-sm font-semibold hover:border-[#2563c7] hover:text-[#174ea6]"
                        >
                            <Download className="size-4" /> Export security CSV
                        </a>
                    </div>
                    {auditEvents.length === 0 ? (
                        <p className="p-5 text-sm text-slate-600">
                            No security events recorded yet.
                        </p>
                    ) : (
                        <div className="divide-y divide-slate-100">
                            {auditEvents.map((event) => (
                                <div
                                    key={event.id}
                                    className="grid gap-1 px-5 py-3 text-sm sm:grid-cols-[1fr_auto]"
                                >
                                    <div>
                                        <span className="font-medium text-slate-900">
                                            {formatAction(event.action)}
                                        </span>{' '}
                                        <span className="text-slate-600">
                                            {event.key_name}
                                        </span>
                                        <p className="mt-1 font-mono text-xs text-slate-500">
                                            {event.key_prefix}…
                                            {event.request_id
                                                ? ` · ${event.request_id}`
                                                : ''}
                                        </p>
                                    </div>
                                    <time className="text-xs text-slate-500">
                                        {event.created_at
                                            ? formatDateTime(event.created_at)
                                            : 'Unknown time'}
                                    </time>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </main>
        </>
    );
}

function KeyPanel({
    apiKey,
    scopeOptions,
    canUseIpAllowLists,
    planLabel,
}: {
    apiKey: ApiKey;
    scopeOptions: ScopeOption[];
    canUseIpAllowLists: boolean;
    planLabel: string;
}) {
    const active = apiKey.status === 'active';
    const usable = active || apiKey.status === 'rotating';

    return (
        <article className="p-5">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-medium">{apiKey.name}</h3>
                        <Status value={apiKey.status} />
                        <span className="border border-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600 uppercase">
                            {apiKey.environment}
                        </span>
                    </div>
                    <p className="mt-1 font-mono text-xs text-slate-500">
                        {apiKey.prefix}…
                    </p>
                    <p className="mt-2 text-xs text-slate-500">
                        {apiKey.last_used_at
                            ? `Last used for ${apiKey.last_used_endpoint_family_label ?? 'an API endpoint'} around ${formatDateTime(apiKey.last_used_at)}`
                            : 'Not used yet'}
                    </p>
                    {apiKey.expires_at && active && (
                        <p className="mt-1 text-xs font-medium text-amber-700">
                            Rotation overlap ends{' '}
                            {formatDateTime(apiKey.expires_at)}
                        </p>
                    )}
                </div>
                {usable && (
                    <Form action={`/api-keys/${apiKey.id}`} method="delete">
                        {({ processing }) => (
                            <Button
                                type="submit"
                                disabled={processing}
                                variant="outline"
                                className="border-red-200 text-red-700 hover:bg-red-50 hover:text-red-800"
                            >
                                Revoke
                            </Button>
                        )}
                    </Form>
                )}
            </div>

            {active && (
                <div className="mt-5 grid gap-5 border-t border-slate-100 pt-5 lg:grid-cols-2">
                    <Form
                        action={`/api-keys/${apiKey.id}`}
                        method="patch"
                        className="space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <h4 className="flex items-center gap-2 text-sm font-semibold">
                                    <Settings2 className="size-4" /> Access
                                    policy
                                </h4>
                                <ScopeFields
                                    scopeOptions={scopeOptions}
                                    selected={apiKey.scopes}
                                    error={errors.scopes}
                                />
                                <IpAllowListField
                                    canUse={canUseIpAllowLists}
                                    planLabel={planLabel}
                                    value={apiKey.ip_allow_list.join('\n')}
                                    error={errors.ip_allow_list}
                                />
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    variant="outline"
                                >
                                    Save policy
                                </Button>
                            </>
                        )}
                    </Form>

                    <Form
                        action={`/api-keys/${apiKey.id}/rotate`}
                        method="post"
                        className="space-y-4 border-t border-slate-100 pt-5 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-5"
                    >
                        {({ processing, errors }) => (
                            <>
                                <h4 className="flex items-center gap-2 text-sm font-semibold">
                                    <RotateCw className="size-4" /> Rotate key
                                </h4>
                                <p className="text-xs leading-5 text-slate-600">
                                    A replacement is created with the same
                                    policy. The old key remains valid only for
                                    the selected overlap.
                                </p>
                                <div className="grid gap-2">
                                    <Label htmlFor={`overlap-${apiKey.id}`}>
                                        Controlled overlap
                                    </Label>
                                    <select
                                        id={`overlap-${apiKey.id}`}
                                        name="overlap_hours"
                                        defaultValue="1"
                                        className="h-10 border border-slate-300 bg-white px-3 text-sm"
                                    >
                                        <option value="0">No overlap</option>
                                        <option value="1">1 hour</option>
                                        <option value="6">6 hours</option>
                                        <option value="24">24 hours</option>
                                    </select>
                                    <FieldError
                                        value={
                                            errors.overlap_hours ??
                                            errors.api_key
                                        }
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    variant="outline"
                                >
                                    Create replacement
                                </Button>
                            </>
                        )}
                    </Form>
                </div>
            )}
        </article>
    );
}

function ScopeFields({
    scopeOptions,
    selected = null,
    error,
}: {
    scopeOptions: ScopeOption[];
    selected?: string[] | null;
    error?: string;
}) {
    return (
        <fieldset className="grid gap-2">
            <legend className="text-sm font-medium">
                Endpoint-family scopes
            </legend>
            <p className="text-xs leading-5 text-slate-500">
                Leave every box clear for unrestricted access, including future
                endpoint families.
            </p>
            <div className="grid gap-2 sm:grid-cols-2">
                {scopeOptions.map((scope) => (
                    <label
                        key={scope.key}
                        className="flex items-start gap-2 text-xs text-slate-700"
                    >
                        <input
                            type="checkbox"
                            name="scopes[]"
                            value={scope.key}
                            defaultChecked={selected?.includes(scope.key)}
                            className="mt-0.5"
                        />
                        {scope.label}
                    </label>
                ))}
            </div>
            <FieldError value={error} />
        </fieldset>
    );
}

function IpAllowListField({
    canUse,
    planLabel,
    value = '',
    error,
}: {
    canUse: boolean;
    planLabel: string;
    value?: string;
    error?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label>IP allow list</Label>
            <textarea
                name="ip_allow_list"
                rows={3}
                defaultValue={value}
                readOnly={!canUse}
                placeholder="203.0.113.10&#10;2001:db8::/48"
                className="w-full border border-slate-300 bg-white px-3 py-2 font-mono text-xs disabled:bg-slate-50 disabled:text-slate-400"
            />
            <p className="text-xs leading-5 text-slate-500">
                {canUse
                    ? 'Optional. One IPv4, IPv6, or CIDR range per line, up to 20 entries.'
                    : `Available on Pro and Scale. Your current plan is ${planLabel}.`}
            </p>
            <FieldError value={error} />
        </div>
    );
}

function SecretNotice({
    newKey,
    command,
}: {
    newKey: CreatedKey;
    command: string;
}) {
    return (
        <div className="border border-amber-300 bg-amber-50 p-5">
            <div className="flex gap-3">
                <ShieldAlert className="mt-0.5 size-5 shrink-0 text-amber-700" />
                <div className="min-w-0 flex-1">
                    <h2 className="font-semibold text-amber-950">
                        Copy this{' '}
                        {newKey.activity === 'rotated' ? 'replacement ' : ''}
                        key now
                    </h2>
                    <p className="mt-1 text-sm leading-6 text-amber-900">
                        This is the only time the full secret for “{newKey.name}
                        ” can be shown.
                        {newKey.activity === 'rotated' &&
                            ` The old key has ${newKey.overlap_hours === 0 ? 'no overlap' : `${newKey.overlap_hours} hour${newKey.overlap_hours === 1 ? '' : 's'} of overlap`}.`}
                    </p>
                    <div className="mt-4 flex items-center gap-2 border border-amber-200 bg-white p-3">
                        <code className="min-w-0 flex-1 text-xs break-all text-slate-800">
                            {newKey.token}
                        </code>
                        <CopyButton text={newKey.token} label="Copy API key" />
                    </div>
                    <div className="mt-3 bg-slate-950 p-3 text-xs leading-5 text-blue-100">
                        <code className="break-all">{command}</code>
                    </div>
                    <div className="mt-3 flex flex-wrap items-center gap-4">
                        <CopyButton
                            text={command}
                            label="Copy request command"
                        />
                        <Link
                            href="/docs#quickstart"
                            className="text-sm font-semibold text-amber-950 underline underline-offset-4"
                        >
                            Open the quickstart →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}

function Status({ value }: { value: string }) {
    const active = value === 'active';
    return (
        <span
            className={`px-2 py-0.5 text-xs font-semibold ${active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}
        >
            {value}
        </span>
    );
}

function CopyButton({ text, label }: { text: string; label: string }) {
    const [status, setStatus] = useState<
        'idle' | 'copying' | 'copied' | 'error'
    >('idle');
    const copy = async () => {
        setStatus('copying');
        try {
            await navigator.clipboard.writeText(text);
            setStatus('copied');
        } catch {
            setStatus('error');
        }
    };

    return (
        <div>
            <button
                type="button"
                onClick={() => void copy()}
                disabled={status === 'copying'}
                className="inline-flex items-center gap-2 p-1.5 text-sm text-amber-800 hover:bg-amber-100 disabled:opacity-50"
            >
                <Copy className="size-4" /> {label}
            </button>
            <p role="status" className="text-xs text-amber-900">
                {status === 'copied' && 'Copied to clipboard.'}
                {status === 'error' &&
                    'Could not copy. Copy the text manually.'}
            </p>
        </div>
    );
}

function FieldError({ value }: { value?: string }) {
    return value ? <p className="text-sm text-red-600">{value}</p> : null;
}

function formatAction(value: string) {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function formatDateTime(value: string) {
    return new Date(value).toLocaleString('en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

ApiKeys.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'API keys', href: '/api-keys' },
    ],
};
