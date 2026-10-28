import { Form, Head } from '@inertiajs/react';
import { Check, LoaderCircle, Play, ShieldCheck, X } from 'lucide-react';
import { useState } from 'react';
import { AdminNav } from '@/components/admin-nav';
import { DashboardPageHeader } from '@/components/dashboard-page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Runtime = {
    environment: string;
    version: string;
    debug: boolean;
    app_url: string;
    api_url: string;
    database: string;
    cache: string;
    queue: string;
    mail: string;
    health_monitoring: boolean;
    scheduler: boolean;
    queue_worker: boolean;
};

type ReadinessCheck = {
    key: string;
    passed: boolean;
    guidance: string;
};

type Service = {
    code: string;
    name: string;
    state: 'available' | 'unavailable' | 'stale' | 'unknown';
    detail: string;
    last_checked_at: string | null;
};

type SmokeResult = {
    name: string;
    status: number | null;
    ok: boolean;
    detail: string;
    durationMs: number;
};

export default function AdminTesting({
    runtime,
    readinessChecks,
    services,
    apiUrl,
    healthResults,
    status,
}: {
    runtime: Runtime;
    readinessChecks: ReadinessCheck[];
    services: Service[];
    apiUrl: string;
    healthResults: Record<string, string> | null;
    status: string | null;
}) {
    const [apiKey, setApiKey] = useState('');
    const [smokeResults, setSmokeResults] = useState<SmokeResult[]>([]);
    const [smokeRunning, setSmokeRunning] = useState(false);
    const passedChecks = readinessChecks.filter((check) => check.passed).length;

    async function runSmokeTests() {
        setSmokeRunning(true);
        setSmokeResults([]);

        const tests = [
            { name: 'Readiness endpoint', path: '/ready', requiresKey: false },
            {
                name: 'OpenAPI document',
                path: '/openapi/v1.yaml',
                requiresKey: false,
            },
            {
                name: apiKey.trim()
                    ? 'Authenticated postcode request'
                    : 'API authentication boundary',
                path: '/v1/postcodes/SW1A1AA',
                requiresKey: true,
            },
        ];
        const results: SmokeResult[] = [];

        for (const test of tests) {
            const startedAt = performance.now();
            try {
                const url = new URL(test.path, apiUrl).toString();
                const headers: Record<string, string> = {
                    Accept: 'application/json',
                };
                if (test.requiresKey && apiKey.trim()) {
                    headers.Authorization = `Bearer ${apiKey.trim()}`;
                }
                const response = await fetch(url, {
                    headers,
                    cache: 'no-store',
                });
                const body = await response.text();
                const ready =
                    test.path === '/ready' &&
                    response.ok &&
                    body.includes('"ready"');
                const openApi =
                    test.path === '/openapi/v1.yaml' &&
                    response.ok &&
                    body.includes('openapi:');
                const authBoundary =
                    test.path.startsWith('/v1/') &&
                    (apiKey.trim() ? response.ok : response.status === 401);

                results.push({
                    name: test.name,
                    status: response.status,
                    ok: ready || openApi || authBoundary,
                    detail: ready
                        ? 'Application reports ready.'
                        : openApi
                          ? 'OpenAPI document loaded.'
                          : authBoundary && !apiKey.trim()
                            ? 'Unauthenticated requests are rejected as expected.'
                            : authBoundary
                              ? 'Authenticated request completed.'
                              : 'Unexpected response from the configured API host.',
                    durationMs: Math.round(performance.now() - startedAt),
                });
            } catch {
                results.push({
                    name: test.name,
                    status: null,
                    ok: false,
                    detail: 'Request could not be completed from this browser.',
                    durationMs: Math.round(performance.now() - startedAt),
                });
            }
        }

        setApiKey('');
        setSmokeResults(results);
        setSmokeRunning(false);
    }

    return (
        <>
            <Head title="Admin testing" />
            <DashboardPageHeader
                eyebrow="Internal"
                title="Testing centre"
                description="Read-only production diagnostics, configuration readiness, provider observations, and controlled API smoke tests."
            />
            <AdminNav />
            <main className="space-y-8 p-5 lg:p-8">
                {status && (
                    <div
                        className="border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-950"
                        role="status"
                    >
                        {status}
                    </div>
                )}

                <section className="grid gap-4 xl:grid-cols-[1.15fr_.85fr]">
                    <div className="border border-slate-200 bg-white p-5">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p className="atlas-kicker">
                                    Deployment snapshot
                                </p>
                                <h2 className="mt-2 text-xl font-semibold">
                                    Runtime configuration
                                </h2>
                            </div>
                            <span className="font-mono text-xs text-slate-500">
                                {runtime.version || 'version unavailable'}
                            </span>
                        </div>
                        <dl className="mt-5 grid gap-4 sm:grid-cols-2">
                            <RuntimeValue
                                label="Environment"
                                value={runtime.environment}
                            />
                            <RuntimeValue
                                label="Debug"
                                value={runtime.debug ? 'Enabled' : 'Disabled'}
                                warning={runtime.debug}
                            />
                            <RuntimeValue
                                label="Web origin"
                                value={runtime.app_url}
                                mono
                            />
                            <RuntimeValue
                                label="API origin"
                                value={runtime.api_url}
                                mono
                            />
                            <RuntimeValue
                                label="Database"
                                value={runtime.database}
                                mono
                            />
                            <RuntimeValue
                                label="Cache"
                                value={runtime.cache}
                                mono
                            />
                            <RuntimeValue
                                label="Queue"
                                value={runtime.queue}
                                mono
                            />
                            <RuntimeValue
                                label="Mail"
                                value={runtime.mail}
                                mono
                            />
                        </dl>
                    </div>

                    <div className="border border-slate-200 bg-white p-5">
                        <p className="atlas-kicker">Maintenance action</p>
                        <h2 className="mt-2 text-xl font-semibold">
                            Run internal health probes
                        </h2>
                        <p className="mt-2 text-sm leading-6 text-slate-600">
                            Probes use the same bounded provider checks as
                            scheduled monitoring. They may consume provider
                            request budget and update health records.
                        </p>
                        <Form
                            action="/admin/testing/health"
                            method="post"
                            className="mt-5"
                        >
                            {({ processing }) => (
                                <Button type="submit" disabled={processing}>
                                    {processing ? (
                                        <LoaderCircle className="animate-spin" />
                                    ) : (
                                        <Play />
                                    )}
                                    {processing
                                        ? 'Running probes…'
                                        : 'Run health probes'}
                                </Button>
                            )}
                        </Form>
                        {healthResults && (
                            <div className="mt-5 space-y-2 border-t border-slate-200 pt-4">
                                {Object.entries(healthResults).map(
                                    ([service, result]) => (
                                        <div
                                            key={service}
                                            className="flex items-center justify-between gap-4 text-sm"
                                        >
                                            <span className="font-mono text-xs text-slate-600">
                                                {service}
                                            </span>
                                            <span
                                                className={
                                                    result === 'available'
                                                        ? 'font-semibold text-emerald-700'
                                                        : 'font-semibold text-rose-700'
                                                }
                                            >
                                                {result}
                                            </span>
                                        </div>
                                    ),
                                )}
                            </div>
                        )}
                    </div>
                </section>

                <section className="border border-slate-200 bg-white p-5">
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p className="atlas-kicker">Configuration gate</p>
                            <h2 className="mt-2 text-xl font-semibold">
                                Production readiness
                            </h2>
                            <p className="mt-2 text-sm text-slate-600">
                                {passedChecks} of {readinessChecks.length}{' '}
                                checks pass. Secrets are represented only as set
                                or missing.
                            </p>
                        </div>
                        <span className="font-mono text-xs text-slate-500">
                            deploy-controlled
                        </span>
                    </div>
                    <div className="mt-5 overflow-x-auto">
                        <table className="w-full min-w-[720px] text-left text-sm">
                            <thead className="border-b border-slate-200 text-xs tracking-wide text-slate-500 uppercase">
                                <tr>
                                    <th className="px-3 py-3">Check</th>
                                    <th className="px-3 py-3">State</th>
                                    <th className="px-3 py-3">Guidance</th>
                                </tr>
                            </thead>
                            <tbody>
                                {readinessChecks.map((check) => (
                                    <tr
                                        key={check.key}
                                        className="border-b border-slate-100 last:border-0"
                                    >
                                        <td className="px-3 py-3 font-mono text-xs">
                                            {check.key}
                                        </td>
                                        <td className="px-3 py-3">
                                            {check.passed ? (
                                                <span className="inline-flex items-center gap-1 font-semibold text-emerald-700">
                                                    <Check className="size-4" />{' '}
                                                    Pass
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 font-semibold text-rose-700">
                                                    <X className="size-4" />{' '}
                                                    Attention
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-3 py-3 text-slate-600">
                                            {check.passed
                                                ? 'Configuration is present.'
                                                : check.guidance}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="grid gap-4 xl:grid-cols-[.9fr_1.1fr]">
                    <div className="border border-slate-200 bg-white p-5">
                        <div className="flex items-start gap-3">
                            <ShieldCheck className="mt-0.5 size-5 text-[#2563c7]" />
                            <div>
                                <p className="atlas-kicker">
                                    Browser smoke tests
                                </p>
                                <h2 className="mt-2 text-xl font-semibold">
                                    Test the production API
                                </h2>
                            </div>
                        </div>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Tests run from this browser against the configured
                            API origin. A key is optional and is cleared from
                            memory after the run. An authenticated postcode
                            request counts against that key’s quota.
                        </p>
                        <label
                            className="mt-5 block text-sm font-semibold"
                            htmlFor="admin-test-api-key"
                        >
                            Temporary API key
                        </label>
                        <Input
                            id="admin-test-api-key"
                            type="password"
                            value={apiKey}
                            onChange={(event) => setApiKey(event.target.value)}
                            autoComplete="off"
                            placeholder="Optional, not stored"
                            className="mt-2"
                        />
                        <Button
                            type="button"
                            onClick={runSmokeTests}
                            disabled={
                                smokeRunning || apiUrl === 'Invalid or unset'
                            }
                            className="mt-4"
                        >
                            {smokeRunning ? (
                                <LoaderCircle className="animate-spin" />
                            ) : (
                                <Play />
                            )}
                            {smokeRunning
                                ? 'Running tests…'
                                : 'Run API smoke tests'}
                        </Button>
                        {smokeResults.length > 0 && (
                            <div className="mt-5 space-y-3 border-t border-slate-200 pt-4">
                                {smokeResults.map((result) => (
                                    <div
                                        key={result.name}
                                        className="flex items-start justify-between gap-4 text-sm"
                                    >
                                        <div>
                                            <p className="font-semibold">
                                                {result.name}
                                            </p>
                                            <p className="mt-1 text-xs text-slate-600">
                                                {result.detail}
                                            </p>
                                        </div>
                                        <span
                                            className={`shrink-0 font-mono text-xs ${result.ok ? 'text-emerald-700' : 'text-rose-700'}`}
                                        >
                                            {result.status ?? 'ERR'} ·{' '}
                                            {result.durationMs} ms
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="border border-slate-200 bg-white p-5">
                        <p className="atlas-kicker">Stored observations</p>
                        <h2 className="mt-2 text-xl font-semibold">
                            Provider health
                        </h2>
                        <div className="mt-5 grid gap-3 sm:grid-cols-2">
                            {services.map((service) => (
                                <div
                                    key={service.code}
                                    className="border border-slate-100 bg-slate-50 p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <p className="font-semibold">
                                            {service.name}
                                        </p>
                                        <span
                                            className={`font-mono text-xs font-bold uppercase ${service.state === 'available' ? 'text-emerald-700' : service.state === 'unavailable' ? 'text-rose-700' : 'text-amber-700'}`}
                                        >
                                            {service.state}
                                        </span>
                                    </div>
                                    <p className="mt-2 text-xs leading-5 text-slate-600">
                                        {service.detail}
                                    </p>
                                    <p className="mt-2 font-mono text-[11px] text-slate-500">
                                        {service.last_checked_at
                                            ? new Date(
                                                  service.last_checked_at,
                                              ).toLocaleString('en-GB')
                                            : 'Not measured'}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}

function RuntimeValue({
    label,
    value,
    mono = false,
    warning = false,
}: {
    label: string;
    value: string;
    mono?: boolean;
    warning?: boolean;
}) {
    return (
        <div>
            <dt className="text-xs font-bold tracking-wide text-slate-500 uppercase">
                {label}
            </dt>
            <dd
                className={`mt-1 text-sm break-all ${mono ? 'font-mono' : 'font-semibold'} ${warning ? 'text-rose-700' : 'text-slate-900'}`}
            >
                {value}
            </dd>
        </div>
    );
}

AdminTesting.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin' },
        { title: 'Testing', href: '/admin/testing' },
    ],
};
