import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    Copy,
    Download,
    History,
    MoreHorizontal,
    Plus,
    RotateCw,
    Settings2,
    ShieldAlert,
} from 'lucide-react';
import { useState } from 'react';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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

type KeyModal = 'policy' | 'rotate' | 'revoke' | null;

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
    const [createOpen, setCreateOpen] = useState(false);
    const newKey = flash.api_key_created;
    const command = newKey
        ? 'curl "https://api.ukapi.co.uk/v1/postcodes/BL2%206XX" -H "Authorization: Bearer ' +
          newKey.token +
          '" -H "Accept: application/json"'
        : '';

    return (
        <>
            <Head title="API keys" />
            <DashboardPageHeader
                eyebrow="Credentials"
                title="API keys"
                description="A focused register for the credentials that connect your systems to UKApi.co.uk."
                action={
                    <Button
                        type="button"
                        onClick={() => setCreateOpen(true)}
                        className="w-full sm:w-auto"
                    >
                        <Plus className="size-4" /> Create key
                    </Button>
                }
            />
            <main className="space-y-8 p-5 lg:p-8">
                {newKey && <SecretNotice newKey={newKey} command={command} />}

                <section className="border border-slate-200 bg-white">
                    <div className="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 px-5 py-4">
                        <div>
                            <p className="atlas-kicker">Credential register</p>
                            <h2 className="mt-2 text-xl font-semibold">
                                Your keys
                            </h2>
                            <p className="mt-1 text-sm text-slate-600">
                                Prefixes and activity are visible here. Policy
                                settings stay one click away.
                            </p>
                        </div>
                        <span className="font-mono text-xs text-slate-500">
                            {apiKeys.length} total
                        </span>
                    </div>
                    {apiKeys.length === 0 ? (
                        <EmptyState title="No keys yet">
                            <p>
                                Create a test key to prepare your integration.
                            </p>
                        </EmptyState>
                    ) : (
                        <div className="divide-y divide-slate-200">
                            {apiKeys.map((key) => (
                                <KeyRow
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

                <section className="border border-slate-200 bg-white">
                    <div className="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 px-5 py-4">
                        <div>
                            <p className="atlas-kicker">Audit trail</p>
                            <h2 className="mt-2 flex items-center gap-2 text-xl font-semibold">
                                <History className="size-4 text-[#2563c7]" />
                                Security activity
                            </h2>
                            <p className="mt-1 text-sm text-slate-600">
                                Key lifecycle and policy changes only. No
                                request contents or bearer secrets.
                            </p>
                        </div>
                        <a
                            href="/api-keys/security-export.csv"
                            className="inline-flex items-center gap-2 border border-slate-300 px-3 py-2 text-sm font-semibold hover:border-[#2563c7] hover:text-[#174ea6]"
                        >
                            <Download className="size-4" /> Export CSV
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
                                    className="grid gap-3 px-5 py-4 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center"
                                >
                                    <span className="flex size-8 items-center justify-center border border-slate-200 bg-slate-50 text-[#2563c7]">
                                        <History className="size-4" />
                                    </span>
                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold text-slate-900">
                                            {formatAction(event.action)}
                                            <span className="font-normal text-slate-500">
                                                {' · ' + event.key_name}
                                            </span>
                                        </p>
                                        <p className="mt-1 truncate font-mono text-xs text-slate-500">
                                            {event.key_prefix}…
                                            {event.request_id
                                                ? ' · ' + event.request_id
                                                : ''}
                                        </p>
                                    </div>
                                    <time className="text-xs text-slate-500 sm:text-right">
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

            <CreateKeyDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
                scopeOptions={scopeOptions}
                canUseIpAllowLists={canUseIpAllowLists}
                planLabel={planLabel}
            />
        </>
    );
}

function CreateKeyDialog({
    open,
    onOpenChange,
    scopeOptions,
    canUseIpAllowLists,
    planLabel,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    scopeOptions: ScopeOption[];
    canUseIpAllowLists: boolean;
    planLabel: string;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[min(760px,calc(100vh-2rem))] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <p className="atlas-kicker">New credential</p>
                    <DialogTitle>Create an API key</DialogTitle>
                    <DialogDescription>
                        Set the identity and access policy once. The secret will
                        be shown exactly once after creation.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    action="/api-keys"
                    method="post"
                    className="space-y-5"
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="new-key-name">Key name</Label>
                                <Input
                                    id="new-key-name"
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
                                <div className="grid gap-2 sm:grid-cols-2">
                                    <label className="flex items-start gap-2 border border-slate-200 p-3 text-sm has-[:checked]:border-[#2563c7] has-[:checked]:bg-blue-50/40">
                                        <input
                                            type="radio"
                                            name="environment"
                                            value="test"
                                            defaultChecked
                                            className="mt-1"
                                        />
                                        <span>
                                            <span className="block font-semibold">
                                                Test
                                            </span>
                                            <span className="mt-1 block text-xs text-slate-500">
                                                Starts with{' '}
                                                <code>uk_test_</code>
                                            </span>
                                        </span>
                                    </label>
                                    <label className="flex items-start gap-2 border border-slate-200 p-3 text-sm has-[:checked]:border-[#2563c7] has-[:checked]:bg-blue-50/40">
                                        <input
                                            type="radio"
                                            name="environment"
                                            value="live"
                                            className="mt-1"
                                        />
                                        <span>
                                            <span className="block font-semibold">
                                                Live
                                            </span>
                                            <span className="mt-1 block text-xs text-slate-500">
                                                Starts with{' '}
                                                <code>uk_live_</code>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <FieldError value={errors.environment} />
                            </fieldset>
                            <ScopeFields
                                scopeOptions={scopeOptions}
                                error={errors.scopes}
                            />
                            <IpAllowListField
                                canUse={canUseIpAllowLists}
                                planLabel={planLabel}
                                error={errors.ip_allow_list}
                                id="new-key-ip-allow-list"
                            />
                            <DialogFooter className="border-t border-slate-200 pt-4">
                                <DialogClose asChild>
                                    <Button type="button" variant="ghost">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button disabled={processing}>
                                    {processing
                                        ? 'Creating key…'
                                        : 'Create API key'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function KeyRow({
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
    const [modal, setModal] = useState<KeyModal>(null);
    const active = apiKey.status === 'active';
    const usable = active || apiKey.status === 'rotating';

    return (
        <>
            <article className="grid gap-4 px-5 py-4 transition-colors hover:bg-[#f7faf8] sm:grid-cols-[minmax(0,1fr)_minmax(220px,.7fr)_auto] sm:items-center">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="truncate font-semibold text-slate-950">
                            {apiKey.name}
                        </h3>
                        <Status value={apiKey.status} />
                        <span className="border border-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600 uppercase">
                            {apiKey.environment}
                        </span>
                    </div>
                    <p className="mt-1 truncate font-mono text-xs text-slate-500">
                        {apiKey.prefix}…
                    </p>
                </div>
                <div className="grid grid-cols-2 gap-4 text-xs sm:grid-cols-1 sm:gap-1">
                    <div>
                        <span className="text-slate-500">Last used</span>
                        <span className="mt-1 block font-medium text-slate-700">
                            {apiKey.last_used_at
                                ? formatDateTime(apiKey.last_used_at)
                                : 'Not used yet'}
                        </span>
                    </div>
                    <div>
                        <span className="text-slate-500">Access</span>
                        <span className="mt-1 block font-medium text-slate-700">
                            {apiKey.scopes?.length
                                ? String(apiKey.scopes.length) +
                                  ' endpoint families'
                                : 'Unrestricted'}
                            {apiKey.ip_allow_list.length > 0
                                ? ' · IP restricted'
                                : ''}
                        </span>
                    </div>
                </div>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            aria-label={'Actions for ' + apiKey.name}
                            className="justify-self-end"
                        >
                            <MoreHorizontal className="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-52">
                        {active && (
                            <DropdownMenuItem
                                onSelect={() => setModal('policy')}
                            >
                                <Settings2 className="size-4" />
                                Edit access policy
                            </DropdownMenuItem>
                        )}
                        {active && (
                            <DropdownMenuItem
                                onSelect={() => setModal('rotate')}
                            >
                                <RotateCw className="size-4" />
                                Rotate key
                            </DropdownMenuItem>
                        )}
                        {usable && (
                            <>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    variant="destructive"
                                    onSelect={() => setModal('revoke')}
                                >
                                    Revoke key
                                </DropdownMenuItem>
                            </>
                        )}
                        {!usable && (
                            <DropdownMenuItem disabled>
                                No actions available
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            </article>

            <Dialog
                open={modal === 'policy'}
                onOpenChange={(open) => !open && setModal(null)}
            >
                <DialogContent className="max-h-[min(760px,calc(100vh-2rem))] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <p className="atlas-kicker">Access policy</p>
                        <DialogTitle>Edit {apiKey.name}</DialogTitle>
                        <DialogDescription>
                            Changes apply to the existing key. The secret itself
                            never changes.
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        action={'/api-keys/' + apiKey.id}
                        method="patch"
                        className="space-y-5"
                        onSuccess={() => setModal(null)}
                    >
                        {({ processing, errors }) => (
                            <>
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
                                    id={'ip-allow-list-' + apiKey.id}
                                />
                                <DialogFooter className="border-t border-slate-200 pt-4">
                                    <DialogClose asChild>
                                        <Button type="button" variant="ghost">
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Saving…' : 'Save policy'}
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={modal === 'rotate'}
                onOpenChange={(open) => !open && setModal(null)}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <p className="atlas-kicker">Credential rotation</p>
                        <DialogTitle>Rotate {apiKey.name}</DialogTitle>
                        <DialogDescription>
                            A replacement keeps the same access policy. Choose
                            how long the old key should remain valid.
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        action={'/api-keys/' + apiKey.id + '/rotate'}
                        method="post"
                        className="space-y-5"
                        onSuccess={() => setModal(null)}
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor={'overlap-' + apiKey.id}>
                                        Controlled overlap
                                    </Label>
                                    <select
                                        id={'overlap-' + apiKey.id}
                                        name="overlap_hours"
                                        defaultValue="1"
                                        className="h-10 border border-slate-300 bg-white px-3 text-sm"
                                    >
                                        <option value="0">No overlap</option>
                                        <option value="1">1 hour</option>
                                        <option value="6">6 hours</option>
                                        <option value="24">24 hours</option>
                                    </select>
                                    <p className="text-xs leading-5 text-slate-500">
                                        The new secret will be shown once after
                                        the replacement is created.
                                    </p>
                                    <FieldError
                                        value={
                                            errors.overlap_hours ??
                                            errors.api_key
                                        }
                                    />
                                </div>
                                <DialogFooter className="border-t border-slate-200 pt-4">
                                    <DialogClose asChild>
                                        <Button type="button" variant="ghost">
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button type="submit" disabled={processing}>
                                        {processing
                                            ? 'Creating replacement…'
                                            : 'Create replacement'}
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={modal === 'revoke'}
                onOpenChange={(open) => !open && setModal(null)}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <p className="atlas-kicker text-rose-700">
                            Destructive action
                        </p>
                        <DialogTitle>Revoke {apiKey.name}?</DialogTitle>
                        <DialogDescription>
                            Any system using this key will stop authenticating.
                            This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="border border-rose-200 bg-rose-50 p-4 text-sm leading-6 text-rose-900">
                        <span className="font-mono">{apiKey.prefix}…</span> will
                        be revoked and recorded in security activity.
                    </div>
                    <Form
                        action={'/api-keys/' + apiKey.id}
                        method="delete"
                        onSuccess={() => setModal(null)}
                    >
                        {({ processing }) => (
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="ghost">
                                        Keep key
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    {processing ? 'Revoking…' : 'Revoke key'}
                                </Button>
                            </DialogFooter>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>
        </>
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
                        className="flex items-start gap-2 border border-slate-200 p-3 text-xs text-slate-700 has-[:checked]:border-[#2563c7] has-[:checked]:bg-blue-50/40"
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
    id,
}: {
    canUse: boolean;
    planLabel: string;
    value?: string;
    error?: string;
    id: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>IP allow list</Label>
            <textarea
                id={id}
                name="ip_allow_list"
                rows={3}
                defaultValue={value}
                readOnly={!canUse}
                placeholder="203.0.113.10&#10;2001:db8::/48"
                className="w-full border border-slate-300 bg-white px-3 py-2 font-mono text-xs read-only:bg-slate-50 read-only:text-slate-400"
            />
            <p className="text-xs leading-5 text-slate-500">
                {canUse
                    ? 'Optional. One IPv4, IPv6, or CIDR range per line, up to 20 entries.'
                    : 'Available on Pro and Scale. Your current plan is ' +
                      planLabel +
                      '.'}
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
                            ' The old key has ' +
                                (newKey.overlap_hours === 0
                                    ? 'no overlap'
                                    : String(newKey.overlap_hours) +
                                      ' hour' +
                                      (newKey.overlap_hours === 1 ? '' : 's') +
                                      ' of overlap') +
                                '.'}
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
    const tone =
        value === 'active'
            ? 'bg-emerald-50 text-emerald-700'
            : value === 'rotating'
              ? 'bg-blue-50 text-blue-700'
              : 'bg-slate-100 text-slate-600';

    return (
        <span className={'px-2 py-0.5 text-xs font-semibold ' + tone}>
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
