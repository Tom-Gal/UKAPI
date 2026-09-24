import { Form, Head, usePage } from '@inertiajs/react';
import { Copy, ShieldAlert } from 'lucide-react';
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
    environment: string;
    status: string;
    last_used_at: string | null;
    created_at: string;
    revoked_at: string | null;
};
type Props = { apiKeys: ApiKey[] };

export default function ApiKeys({ apiKeys }: Props) {
    const { flash } = usePage<{
        flash: { api_key_created?: { name: string; token: string } };
    }>().props;
    const newKey = flash.api_key_created;
    return (
        <>
            <Head title="API keys" />
            <DashboardPageHeader
                eyebrow="Credentials"
                title="API keys"
                description="Create separate keys for local development and production. Secret values are shown exactly once."
            />
            <main className="grid gap-6 p-5 lg:p-8 xl:grid-cols-[.8fr_1.2fr]">
                <section className="rounded-xl border border-slate-200 bg-white p-6">
                    <h2 className="font-semibold">Create an API key</h2>
                    <p className="mt-2 text-sm leading-6 text-slate-600">
                        Choose a memorable label. The environment prefix is
                        visible in the resulting credential.
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
                                        placeholder="Local development"
                                    />
                                    <p className="text-sm text-red-600">
                                        {errors.name}
                                    </p>
                                </div>
                                <fieldset className="grid gap-2">
                                    <legend className="text-sm font-medium">
                                        Environment
                                    </legend>
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="radio"
                                            name="environment"
                                            value="test"
                                            defaultChecked
                                        />{' '}
                                        Test — starts with <code>uk_test_</code>
                                    </label>
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="radio"
                                            name="environment"
                                            value="live"
                                        />{' '}
                                        Live — starts with <code>uk_live_</code>
                                    </label>
                                    <p className="text-sm text-red-600">
                                        {errors.environment}
                                    </p>
                                </fieldset>
                                <Button
                                    disabled={processing}
                                    className="w-full bg-[#1248e8] hover:bg-[#0f3dc4]"
                                >
                                    {processing
                                        ? 'Creating key…'
                                        : 'Create API key'}
                                </Button>
                            </>
                        )}
                    </Form>
                </section>
                <section>
                    {newKey && (
                        <div className="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-5">
                            <div className="flex gap-3">
                                <ShieldAlert className="mt-0.5 size-5 shrink-0 text-amber-700" />
                                <div>
                                    <h2 className="font-semibold text-amber-950">
                                        Copy this key now
                                    </h2>
                                    <p className="mt-1 text-sm leading-6 text-amber-900">
                                        This is the only time the full secret
                                        for “{newKey.name}” can be shown.
                                    </p>
                                    <div className="mt-4 flex items-center gap-2 rounded-lg border border-amber-200 bg-white p-3">
                                        <code className="min-w-0 flex-1 text-xs break-all text-slate-800">
                                            {newKey.token}
                                        </code>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                void navigator.clipboard.writeText(
                                                    newKey.token,
                                                )
                                            }
                                            className="rounded p-1.5 text-amber-800 hover:bg-amber-100"
                                            aria-label="Copy API key"
                                        >
                                            <Copy className="size-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                    <div className="rounded-xl border border-slate-200 bg-white">
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
                            <div className="divide-y divide-slate-100">
                                {apiKeys.map((key) => (
                                    <div
                                        key={key.id}
                                        className="flex flex-wrap items-center justify-between gap-4 p-5"
                                    >
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <h3 className="font-medium">
                                                    {key.name}
                                                </h3>
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs font-semibold ${key.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}
                                                >
                                                    {key.status}
                                                </span>
                                            </div>
                                            <p className="mt-1 font-mono text-xs text-slate-500">
                                                {key.prefix}…
                                            </p>
                                            <p className="mt-2 text-xs text-slate-500">
                                                {key.last_used_at
                                                    ? `Last used ${new Date(key.last_used_at).toLocaleString()}`
                                                    : 'Not used yet'}
                                            </p>
                                        </div>
                                        {key.status === 'active' && (
                                            <Form
                                                action={`/api-keys/${key.id}`}
                                                method="delete"
                                            >
                                                <Button
                                                    type="submit"
                                                    variant="outline"
                                                    className="border-red-200 text-red-700 hover:bg-red-50 hover:text-red-800"
                                                >
                                                    Revoke
                                                </Button>
                                            </Form>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </section>
            </main>
        </>
    );
}

ApiKeys.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'API keys', href: '/api-keys' },
    ],
};
