import { Head, Link, usePage } from '@inertiajs/react';
import {
    Check,
    ChevronDown,
    Copy,
    KeyRound,
    Play,
    RotateCcw,
    Send,
    Terminal,
} from 'lucide-react';
import { useMemo, useState, type FormEvent } from 'react';
import { endpointFamilies } from '@/lib/ukapi-content';

type ExplorerEndpoint = {
    id: string;
    family: string;
    path: string;
    examplePath: string;
    description: string;
};

const endpoints: ExplorerEndpoint[] = endpointFamilies.flatMap((family) =>
    family.endpoints.map((endpoint, index) => ({
        id: `${family.slug}-${index + 1}`,
        family: family.name,
        path: endpoint.path,
        examplePath: endpoint.examplePath ?? endpoint.path,
        description: endpoint.description,
    })),
);

function selectedEndpointId(search: string): string {
    const value = new URLSearchParams(search).get('endpoint');
    return value !== null && endpoints.some((endpoint) => endpoint.id === value)
        ? value
        : endpoints[0]?.id ?? '';
}

export default function TryIt() {
    const page = usePage();
    const initialEndpoint = selectedEndpointId(page.url.split('?')[1] ?? '');
    const [endpointId, setEndpointId] = useState(initialEndpoint);
    const [requestPath, setRequestPath] = useState(
        endpoints.find((endpoint) => endpoint.id === initialEndpoint)
            ?.examplePath ?? '',
    );
    const [apiKey, setApiKey] = useState('');
    const [response, setResponse] = useState('');
    const [status, setStatus] = useState<number | null>(null);
    const [elapsed, setElapsed] = useState<number | null>(null);
    const [isLoading, setIsLoading] = useState(false);
    const [copied, setCopied] = useState<'url' | 'response' | null>(null);

    const endpoint = endpoints.find((item) => item.id === endpointId);
    const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL ?? '').replace(
        /\/$/,
        '',
    );
    const requestUrl = `${apiBaseUrl}${requestPath}`;
    const displayUrl = `${apiBaseUrl || 'https://api.ukapi.io'}${requestPath}`;
    const curl = `curl "${displayUrl}" \\\n+  -H "Accept: application/json" \\\n+  -H "Authorization: Bearer uk_test_…"`;

    const responseTone = useMemo(() => {
        if (status === null) return 'text-slate-400';
        return status < 400 ? 'text-emerald-300' : 'text-rose-300';
    }, [status]);

    function changeEndpoint(id: string) {
        const nextEndpoint = endpoints.find((item) => item.id === id);
        setEndpointId(id);
        setRequestPath(nextEndpoint?.examplePath ?? '');
        setResponse('');
        setStatus(null);
        setElapsed(null);
    }

    async function copy(value: string, item: 'url' | 'response') {
        try {
            await navigator.clipboard.writeText(value);
            setCopied(item);
            window.setTimeout(() => setCopied(null), 1800);
        } catch {
            setCopied(null);
        }
    }

    async function runRequest(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setIsLoading(true);
        setResponse('');
        setStatus(null);
        setElapsed(null);
        const startedAt = performance.now();

        try {
            const result = await fetch(requestUrl, {
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${apiKey.trim()}`,
                },
            });
            const rawResponse = await result.text();
            let body = rawResponse;

            try {
                body = JSON.stringify(JSON.parse(rawResponse), null, 2);
            } catch {
                // Keep non-JSON responses readable in the console.
            }

            setStatus(result.status);
            setResponse(body || 'No response body returned.');
        } catch {
            setResponse(
                'Unable to reach the API. Check the API base URL and your connection, then try again.',
            );
        } finally {
            setElapsed(Math.round(performance.now() - startedAt));
            setIsLoading(false);
        }
    }

    return (
        <>
            <Head title="Try it out">
                <meta
                    name="description"
                    content="Send a live UKAPI.io request from an in-browser API explorer."
                />
            </Head>
            <section className="border-b border-slate-200 bg-[radial-gradient(circle_at_75%_-20%,#dbeafe_0,transparent_36%),linear-gradient(180deg,#fff_0%,#f7f9fc_100%)]">
                <div className="mx-auto max-w-6xl px-5 py-12 lg:px-8 lg:py-16">
                    <Link
                        href="/docs"
                        className="text-sm font-semibold text-[#1248e8] hover:text-[#0f3dc4]"
                    >
                        Documentation
                    </Link>
                    <div className="mt-6 flex flex-col justify-between gap-8 lg:flex-row lg:items-end">
                        <div className="max-w-2xl">
                            <div className="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white/80 px-3 py-1.5 text-xs font-bold tracking-[.14em] text-[#1248e8] uppercase shadow-sm">
                                <Terminal className="size-3.5" />
                                API explorer
                            </div>
                            <h1 className="mt-5 text-4xl font-semibold tracking-[-.05em] text-slate-950 sm:text-5xl">
                                Make your first live request.
                            </h1>
                            <p className="mt-4 max-w-xl text-lg leading-8 text-slate-600">
                                Pick an endpoint, adjust its path or query, and
                                inspect the real response without leaving the
                                docs.
                            </p>
                        </div>
                        <div className="max-w-xs rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950">
                            <span className="font-semibold">Test keys only.</span>{' '}
                            Your key is used for this request and never saved.
                        </div>
                    </div>
                </div>
            </section>

            <div className="mx-auto max-w-6xl px-5 py-8 lg:px-8 lg:py-12">
                <form onSubmit={runRequest} className="grid gap-5 lg:grid-cols-[minmax(0,0.93fr)_minmax(0,1.07fr)]">
                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_12px_32px_rgba(15,23,42,.06)]">
                        <div className="border-b border-slate-100 px-5 py-5">
                            <p className="text-xs font-bold tracking-[.14em] text-slate-500 uppercase">
                                01 · Configure request
                            </p>
                            <h2 className="mt-1 text-xl font-semibold tracking-tight">
                                Choose what to call
                            </h2>
                        </div>
                        <div className="space-y-6 p-5">
                            <label className="block">
                                <span className="text-sm font-semibold text-slate-800">
                                    Endpoint
                                </span>
                                <span className="relative mt-2 block">
                                    <select
                                        value={endpointId}
                                        onChange={(event) => changeEndpoint(event.target.value)}
                                        className="w-full appearance-none rounded-xl border border-slate-300 bg-white px-4 py-3 pr-10 font-mono text-sm text-slate-950 outline-none transition focus:border-[#1248e8] focus:ring-4 focus:ring-blue-100"
                                    >
                                        {endpointFamilies.map((family) => (
                                            <optgroup key={family.slug} label={family.name}>
                                                {family.endpoints.map((item, index) => (
                                                    <option key={`${item.path}-${index}`} value={`${family.slug}-${index + 1}`}>
                                                        GET {item.path}
                                                    </option>
                                                ))}
                                            </optgroup>
                                        ))}
                                    </select>
                                    <ChevronDown className="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-slate-500" />
                                </span>
                                <span className="mt-2 block text-sm leading-6 text-slate-500">
                                    {endpoint?.description}
                                </span>
                            </label>

                            <label className="block">
                                <span className="text-sm font-semibold text-slate-800">
                                    Path and query
                                </span>
                                <span className="mt-2 flex overflow-hidden rounded-xl border border-slate-300 bg-white focus-within:border-[#1248e8] focus-within:ring-4 focus-within:ring-blue-100">
                                    <span className="flex items-center border-r border-slate-200 bg-slate-50 px-3 font-mono text-xs font-bold text-emerald-700">
                                        GET
                                    </span>
                                    <input
                                        value={requestPath}
                                        onChange={(event) => setRequestPath(event.target.value)}
                                        required
                                        spellCheck={false}
                                        aria-label="Request path and query"
                                        className="min-w-0 flex-1 px-3 py-3 font-mono text-sm text-slate-900 outline-none"
                                    />
                                </span>
                                <span className="mt-2 block text-xs leading-5 text-slate-500">
                                    Edit values directly—for example, change a
                                    postcode or add a query parameter.
                                </span>
                            </label>

                            <label className="block">
                                <span className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                    <KeyRound className="size-4 text-[#1248e8]" />
                                    Test API key
                                </span>
                                <input
                                    value={apiKey}
                                    onChange={(event) => setApiKey(event.target.value)}
                                    type="password"
                                    autoComplete="off"
                                    spellCheck={false}
                                    required
                                    placeholder="uk_test_k_…"
                                    className="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 font-mono text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1248e8] focus:ring-4 focus:ring-blue-100"
                                />
                            </label>

                            <div className="flex flex-wrap items-center gap-3 pt-1">
                                <button
                                    type="submit"
                                    disabled={isLoading || apiKey.trim() === '' || requestPath.trim() === ''}
                                    className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#1248e8] px-5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#0f3dc4] hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8] disabled:cursor-not-allowed disabled:transform-none disabled:opacity-50"
                                >
                                    {isLoading ? <Send className="size-4 animate-pulse" /> : <Play className="size-4 fill-current" />}
                                    {isLoading ? 'Sending request…' : 'Send request'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setRequestPath(endpoint?.examplePath ?? '');
                                        setResponse('');
                                        setStatus(null);
                                        setElapsed(null);
                                    }}
                                    className="inline-flex min-h-11 items-center gap-2 rounded-xl px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                                >
                                    <RotateCcw className="size-4" />
                                    Reset
                                </button>
                            </div>
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-2xl border border-slate-800 bg-[#0d1529] shadow-[0_12px_32px_rgba(15,23,42,.16)]">
                        <div className="flex items-center justify-between border-b border-white/10 px-5 py-5">
                            <div>
                                <p className="text-xs font-bold tracking-[.14em] text-blue-200 uppercase">
                                    02 · Live response
                                </p>
                                <h2 className="mt-1 text-xl font-semibold tracking-tight text-white">
                                    Response console
                                </h2>
                            </div>
                            {response && (
                                <button
                                    type="button"
                                    onClick={() => copy(response, 'response')}
                                    className="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-xs font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white"
                                >
                                    {copied === 'response' ? <Check className="size-3.5 text-emerald-300" /> : <Copy className="size-3.5" />}
                                    {copied === 'response' ? 'Copied' : 'Copy'}
                                </button>
                            )}
                        </div>
                        <div className="flex min-h-[465px] flex-col">
                            <div className="border-b border-white/10 px-5 py-4">
                                <div className="flex items-center justify-between gap-3">
                                    <span className="font-mono text-xs text-slate-400">GET</span>
                                    <button
                                        type="button"
                                        onClick={() => copy(displayUrl, 'url')}
                                        className="inline-flex min-w-0 items-center gap-1.5 font-mono text-xs text-blue-200 hover:text-white"
                                        title="Copy request URL"
                                    >
                                        <span className="max-w-[250px] truncate sm:max-w-[370px]">{displayUrl}</span>
                                        {copied === 'url' ? <Check className="size-3.5 shrink-0 text-emerald-300" /> : <Copy className="size-3.5 shrink-0" />}
                                    </button>
                                </div>
                            </div>
                            {response ? (
                                <>
                                    <div className="flex items-center gap-3 border-b border-white/10 px-5 py-3 font-mono text-xs">
                                        <span className={responseTone}>
                                            {status ? `${status} ${status < 400 ? 'OK' : 'Error'}` : 'Network error'}
                                        </span>
                                        {elapsed !== null && <span className="text-slate-500">{elapsed} ms</span>}
                                    </div>
                                    <pre className="flex-1 overflow-auto p-5 font-mono text-xs leading-6 whitespace-pre-wrap text-blue-100"><code>{response}</code></pre>
                                </>
                            ) : (
                                <div className="flex flex-1 flex-col items-center justify-center px-8 text-center">
                                    <div className="flex size-12 items-center justify-center rounded-2xl bg-blue-400/10 text-blue-200">
                                        <Terminal className="size-6" />
                                    </div>
                                    <p className="mt-5 font-medium text-slate-200">Your response will appear here.</p>
                                    <p className="mt-2 max-w-xs text-sm leading-6 text-slate-400">
                                        Add a test key and send the request to
                                        inspect headers, status and JSON.
                                    </p>
                                </div>
                            )}
                        </div>
                    </section>
                </form>

                <section className="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div className="flex flex-col justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center">
                        <div>
                            <p className="text-sm font-semibold text-slate-900">Equivalent cURL request</p>
                            <p className="mt-1 text-xs text-slate-500">Use this as a starting point in your app or terminal.</p>
                        </div>
                        <button type="button" onClick={() => copy(curl, 'url')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-[#1248e8] hover:text-[#0f3dc4]">
                            {copied === 'url' ? <Check className="size-4" /> : <Copy className="size-4" />}
                            {copied === 'url' ? 'Copied' : 'Copy cURL'}
                        </button>
                    </div>
                    <pre className="overflow-x-auto bg-slate-50 px-5 py-4 font-mono text-xs leading-6 text-slate-700"><code>{curl}</code></pre>
                </section>
            </div>
        </>
    );
}
