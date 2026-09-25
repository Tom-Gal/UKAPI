import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, LockKeyhole } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { endpointFamilies } from '@/lib/ukapi-content';

export default function EndpointReference() {
    const page = usePage();
    const parts = page.url.split('/').filter(Boolean);
    const family = endpointFamilies.find((item) => item.slug === parts.at(-2));
    const endpoint = family?.endpoints[Number(parts.at(-1)) - 1];

    if (!family || !endpoint) {
        return (
            <>
                <Head title="Endpoint reference" />
                <div className="mx-auto max-w-4xl px-5 py-20 lg:px-8">
                    Reference not found.
                </div>
            </>
        );
    }

    const requestPath =
        endpoint.examplePath ?? endpoint.path.replace(/\{[^}]+\}/g, 'EXAMPLE');
    const requestUrl = `https://api.ukapi.io${requestPath}`;
    const isLive = endpoint.live === true;
    const curl = [
        `curl ${requestUrl}`,
        '-H "Authorization: Bearer uk_test_…"',
    ].join(' \\\n  ');
    const successResponse =
        endpoint.exampleResponse ??
        `{\n  "data": {},\n  "meta": {\n    "request_id": "req_01…",\n    "cached": true,\n    "source": "${endpoint.source}",\n    "source_updated_at": "2026-09-23T12:30:00Z"\n  }\n}`;

    return (
        <>
            <Head title={`${endpoint.path} reference`} />
            <article className="mx-auto max-w-4xl px-5 py-14 lg:px-8">
                <Link
                    href={`/docs/${family.slug}`}
                    className="inline-flex items-center gap-1 text-sm font-semibold text-[#1248e8]"
                >
                    <ArrowLeft className="size-4" />
                    {family.name}
                </Link>
                <div className="mt-8">
                    <p className="font-mono text-sm font-semibold text-[#1248e8]">
                        {endpoint.method}
                    </p>
                    <h1 className="mt-2 font-mono text-3xl font-semibold tracking-[-.035em] break-all">
                        {endpoint.path}
                    </h1>
                    <p className="mt-5 text-lg leading-8 text-slate-600">
                        {endpoint.description}
                    </p>
                    {isLive ? (
                        <span className="mt-4 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                            Live endpoint
                        </span>
                    ) : (
                        <span className="mt-4 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                            Planned endpoint
                        </span>
                    )}
                </div>
                <div className="mt-10 grid gap-4 sm:grid-cols-3">
                    <Fact label="Coverage" value={endpoint.coverage} />
                    <Fact label="Freshness" value={endpoint.freshness} />
                    <Fact label="Source" value={endpoint.source} />
                </div>
                <Section title="Authentication">
                    <p>
                        Requires a valid customer API key in the{' '}
                        <code>Authorization: Bearer</code> header. Browser
                        sessions are never used for public API requests.
                    </p>
                </Section>
                <Section title="Parameters">
                    {endpoint.parameters ? (
                        <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                            <table className="w-full min-w-[620px] text-left text-sm">
                                <thead className="bg-slate-50 text-xs tracking-wide text-slate-500 uppercase">
                                    <tr>
                                        <th className="px-4 py-3">Name</th>
                                        <th className="px-4 py-3">In</th>
                                        <th className="px-4 py-3">Required</th>
                                        <th className="px-4 py-3">
                                            Description
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {endpoint.parameters.map((parameter) => (
                                        <tr
                                            key={`${parameter.location}-${parameter.name}`}
                                            className="border-t border-slate-100"
                                        >
                                            <td className="px-4 py-3 font-mono text-slate-900">
                                                {parameter.name}
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">
                                                {parameter.location}
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">
                                                {parameter.required
                                                    ? 'Yes'
                                                    : 'No'}
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">
                                                {parameter.description}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p>
                            Supply the path and query parameters shown in the
                            endpoint path. Inputs are validated before an
                            upstream provider is called; invalid parameters
                            return a 400 or 422 response.
                        </p>
                    )}
                </Section>
                <Section title="Example request">
                    <pre className="overflow-x-auto rounded-lg bg-[#10182d] p-5 font-mono text-sm leading-6 text-blue-100">
                        <code>{curl}</code>
                    </pre>
                </Section>
                <Section title="Examples in common stacks">
                    <div className="grid gap-4 lg:grid-cols-2">
                        <Snippet
                            language="PHP / Laravel"
                            code={[
                                "$response = Http::withToken(config('services.ukapi.key'))",
                                `    ->get('${requestUrl}');`,
                                '',
                                "$data = $response->throw()->json('data');",
                            ].join('\n')}
                        />
                        <Snippet
                            language="JavaScript / TypeScript"
                            code={[
                                `const response = await fetch('${requestUrl}', {`,
                                '  headers: { Authorization: `Bearer ${apiKey}` },',
                                '});',
                                '',
                                'const { data } = await response.json();',
                            ].join('\n')}
                        />
                        <Snippet
                            language="C#"
                            code={[
                                'using System.Net.Http.Headers;',
                                '',
                                'using var client = new HttpClient();',
                                'client.DefaultRequestHeaders.Authorization =',
                                '    new AuthenticationHeaderValue("Bearer", apiKey);',
                                '',
                                `var response = await client.GetAsync("${requestPath}");`,
                                'response.EnsureSuccessStatusCode();',
                            ].join('\n')}
                        />
                    </div>
                </Section>
                <Section title="Example success response">
                    <pre className="overflow-x-auto rounded-lg bg-[#10182d] p-5 font-mono text-sm leading-6 text-blue-100">
                        <code>{successResponse}</code>
                    </pre>
                </Section>
                <Section title="Errors">
                    <p>
                        <code>invalid_api_key</code>,{' '}
                        <code>invalid_parameter</code>,{' '}
                        <code>invalid_company_number</code>,{' '}
                        <code>company_not_found</code>,{' '}
                        <code>invalid_sic_code</code>,{' '}
                        <code>sic_not_found</code>,{' '}
                        <code>sic_reference_unavailable</code>,{' '}
                        <code>rate_limit_exceeded</code>,{' '}
                        <code>quota_exceeded</code>,{' '}
                        <code>upstream_unavailable</code>,{' '}
                        <code>upstream_invalid_response</code>, and
                        endpoint-specific not-found errors use the standard
                        error envelope.
                    </p>
                </Section>
                {isLive ? (
                    <TryItConsole requestPath={requestPath} />
                ) : (
                    <Section title="Availability">
                        <p>
                            This route is documented as a planned contract but
                            is not deployed yet. Do not send production traffic
                            to it until it is marked live.
                        </p>
                    </Section>
                )}
            </article>
        </>
    );
}

function TryItConsole({ requestPath }: { requestPath: string }) {
    const [apiKey, setApiKey] = useState('');
    const [response, setResponse] = useState('');
    const [status, setStatus] = useState<number | null>(null);
    const [isLoading, setIsLoading] = useState(false);

    const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL ?? '').replace(
        /\/$/,
        '',
    );
    const requestUrl = `${apiBaseUrl}${requestPath}`;

    async function runRequest(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setIsLoading(true);
        setResponse('');
        setStatus(null);

        try {
            const result = await fetch(requestUrl, {
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${apiKey.trim()}`,
                },
            });
            const body = await result.json();

            setStatus(result.status);
            setResponse(JSON.stringify(body, null, 2));
        } catch {
            setResponse(
                'Unable to reach the API. Check your connection and API base URL.',
            );
        } finally {
            setIsLoading(false);
        }
    }

    return (
        <section className="mt-10 rounded-xl border border-blue-200 bg-blue-50/60 p-5">
            <div className="flex items-start gap-3">
                <LockKeyhole className="mt-0.5 size-4 shrink-0 text-[#1248e8]" />
                <div>
                    <h2 className="font-semibold text-slate-950">
                        Try this endpoint
                    </h2>
                    <p className="mt-1 text-sm leading-6 text-slate-600">
                        Use a test key from your dashboard. It stays in this
                        browser tab only and is never saved by UKAPI.io.
                    </p>
                </div>
            </div>
            <form className="mt-5" onSubmit={runRequest}>
                <label
                    htmlFor="try-it-api-key"
                    className="text-sm font-semibold text-slate-800"
                >
                    Test API key
                </label>
                <div className="mt-2 flex flex-col gap-3 sm:flex-row">
                    <input
                        id="try-it-api-key"
                        value={apiKey}
                        onChange={(event) => setApiKey(event.target.value)}
                        type="password"
                        autoComplete="off"
                        spellCheck={false}
                        required
                        placeholder="uk_test_k_…"
                        className="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm text-slate-900 shadow-sm outline-none placeholder:text-slate-400 focus:border-[#1248e8] focus:ring-2 focus:ring-blue-100"
                    />
                    <button
                        type="submit"
                        disabled={isLoading || apiKey.trim() === ''}
                        className="inline-flex shrink-0 items-center justify-center rounded-lg bg-[#1248e8] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0f3dc4] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {isLoading ? 'Sending…' : 'Send request'}
                    </button>
                </div>
            </form>
            {response !== '' && (
                <div className="mt-5 overflow-hidden rounded-lg border border-slate-700 bg-[#10182d]">
                    <p className="border-b border-slate-700 px-4 py-2 font-mono text-xs text-slate-400">
                        Response {status !== null ? `· ${status}` : ''}
                    </p>
                    <pre className="overflow-x-auto p-4 font-mono text-xs leading-6 text-blue-100">
                        <code>{response}</code>
                    </pre>
                </div>
            )}
        </section>
    );
}

function Fact({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4">
            <p className="text-xs font-bold tracking-wide text-slate-500 uppercase">
                {label}
            </p>
            <p className="mt-2 text-sm font-medium text-slate-800">{value}</p>
        </div>
    );
}

function Section({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="mt-10">
            <h2 className="text-lg font-semibold">{title}</h2>
            <div className="mt-3 text-sm leading-7 text-slate-600">
                {children}
            </div>
        </section>
    );
}

function Snippet({ language, code }: { language: string; code: string }) {
    return (
        <div className="overflow-hidden rounded-lg border border-slate-700 bg-[#10182d]">
            <p className="border-b border-slate-700 px-4 py-2 font-mono text-xs text-slate-400">
                {language}
            </p>
            <pre className="overflow-x-auto p-4 font-mono text-xs leading-6 text-blue-100">
                <code>{code}</code>
            </pre>
        </div>
    );
}
