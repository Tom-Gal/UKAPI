import { Head, Link } from '@inertiajs/react';
import {
    Check,
    Copy,
    ExternalLink,
    FileCode2,
    ShieldCheck,
} from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { endpointFamilies, type Endpoint } from '@/lib/ukapi-content';

type DocsProps = { section?: string };

const sections = [
    ['quickstart', 'Quickstart'],
    ['authentication', 'Authentication'],
    ['responses', 'Responses'],
    ['errors', 'Errors'],
    ['rate-limits', 'Rate limits'],
    ['api-reference', 'API reference'],
    ['sources-and-coverage', 'Sources & coverage'],
] as const;

const routeSectionMap: Record<string, string> = {
    overview: 'quickstart',
    'getting-started': 'quickstart',
    authentication: 'authentication',
    errors: 'errors',
    'rate-limits': 'rate-limits',
    'api-reference': 'api-reference',
    'sources-and-coverage': 'sources-and-coverage',
};

export default function Docs({ section }: DocsProps) {
    useEffect(() => {
        if (window.location.hash) return;
        const target = section ? routeSectionMap[section] : undefined;
        if (!target) return;

        const timeout = window.setTimeout(() => {
            document.getElementById(target)?.scrollIntoView();
        }, 50);

        return () => window.clearTimeout(timeout);
    }, [section]);

    return (
        <>
            <Head title="Documentation">
                <meta
                    name="description"
                    content="A complete, practical guide to making reliable UKApi.co.uk requests."
                />
            </Head>
            <div className="border-b border-slate-300 bg-white">
                <div className="atlas-shell flex items-center gap-2 py-4 text-xs font-medium text-slate-500">
                    <Link href="/" className="hover:text-[#2563c7]">
                        UKApi.co.uk
                    </Link>
                    <span aria-hidden="true">/</span>
                    <span className="text-slate-800">Documentation</span>
                </div>
            </div>

            <div className="bg-[#f5f7f4]">
                <div className="atlas-shell lg:grid lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-12">
                    <DocsNavigation />
                    <main className="min-w-0 py-10 lg:py-16">
                        <article className="max-w-4xl">
                            <header className="border-b border-slate-950 pb-12">
                                <p className="atlas-kicker">
                                    UKApi.co.uk API · v1
                                </p>
                                <h1 className="mt-4 text-5xl font-semibold tracking-[-0.045em] text-slate-950 sm:text-6xl">
                                    Documentation
                                </h1>
                                <p className="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                                    Everything needed to make a first request,
                                    handle failures and understand the live
                                    endpoints—on one page.
                                </p>
                            </header>

                            <DocSection
                                id="quickstart"
                                label="01"
                                title="Make your first request"
                                intro="Create a key, send one request and keep the response metadata. You can complete the whole quickstart without leaving this page."
                            >
                                <ol className="grid border-t border-l border-slate-300 sm:grid-cols-3">
                                    <Step number="1" title="Create a key">
                                        Create a free account, open API keys and
                                        copy the secret when it is shown.
                                    </Step>
                                    <Step number="2" title="Send it as Bearer">
                                        Make requests from your server and
                                        include the key in the Authorization
                                        header.
                                    </Step>
                                    <Step
                                        number="3"
                                        title="Keep the request ID"
                                    >
                                        Log the request ID so failures can be
                                        traced without storing sensitive request
                                        data.
                                    </Step>
                                </ol>
                                <CodeBlock
                                    title="First request"
                                    code={`curl "https://api.ukapi.co.uk/v1/postcodes/BL2%206XX" \\\n+  -H "Authorization: Bearer uk_live_your_api_key" \\\n+  -H "Accept: application/json"`}
                                />
                                <Callout title="Keep keys out of client code">
                                    Do not place an API key in browser
                                    JavaScript, a mobile app or public source
                                    control. Call UKApi.co.uk through your own
                                    backend.
                                </Callout>
                            </DocSection>

                            <DocSection
                                id="authentication"
                                label="02"
                                title="Authentication"
                                intro="Every /v1 request uses a standard Bearer token. Test and live keys use the same header format."
                            >
                                <CodeBlock
                                    title="Required headers"
                                    code={`Authorization: Bearer uk_live_your_api_key
Accept: application/json`}
                                />
                                <div className="grid gap-8 border-t border-slate-300 pt-6 sm:grid-cols-2">
                                    <Info title="Test keys">
                                        Use while developing and testing. Keep
                                        them in an environment variable or
                                        secret manager.
                                    </Info>
                                    <Info title="Live keys">
                                        Use only in production. Create a
                                        replacement before revoking a key during
                                        rotation.
                                    </Info>
                                </div>
                                <p className="mt-6 leading-7 text-slate-600">
                                    A missing, revoked or malformed key returns{' '}
                                    <InlineCode>401</InlineCode> with the stable
                                    code{' '}
                                    <InlineCode>invalid_api_key</InlineCode>.
                                </p>
                            </DocSection>

                            <DocSection
                                id="responses"
                                label="03"
                                title="Response shape"
                                intro="Successful requests use the same data and meta envelope, regardless of the upstream source."
                            >
                                <CodeBlock
                                    title="Successful response"
                                    code={`{
  "data": { "postcode": "BL2 6XX", "country": "England" },
  "meta": {
    "request_id": "req_01…",
    "cached": false,
    "source": "postcodes_io",
    "coverage": "UK"
  }
}`}
                                />
                                <dl className="mt-6 grid border-t border-l border-slate-300 sm:grid-cols-2">
                                    <Definition term="data">
                                        The requested resource or result set.
                                    </Definition>
                                    <Definition term="request_id">
                                        A safe identifier to retain for support
                                        and tracing.
                                    </Definition>
                                    <Definition term="source">
                                        The public or calculated source behind
                                        the result.
                                    </Definition>
                                    <Definition term="coverage">
                                        The geography represented by the
                                        endpoint or result.
                                    </Definition>
                                </dl>
                            </DocSection>

                            <DocSection
                                id="errors"
                                label="04"
                                title="Errors"
                                intro="Branch on the machine-readable code rather than matching message text. The request ID is safe to include in a support request."
                            >
                                <CodeBlock
                                    title="Error response"
                                    code={`{
  "error": {
    "code": "invalid_parameter",
    "message": "One or more request parameters are invalid.",
    "status": 422,
    "request_id": "req_01…"
  }
}`}
                                />
                                <div className="mt-6 overflow-x-auto border border-slate-300 bg-white">
                                    <table className="w-full min-w-[620px] text-left text-sm">
                                        <thead className="bg-slate-100 font-mono text-[10px] tracking-wider text-slate-600 uppercase">
                                            <tr>
                                                <th className="px-4 py-3">
                                                    Status
                                                </th>
                                                <th className="px-4 py-3">
                                                    Code
                                                </th>
                                                <th className="px-4 py-3">
                                                    What to do
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-200 text-slate-600">
                                            <ErrorRow
                                                status="401"
                                                code="invalid_api_key"
                                                action="Check that the key is active and sent as a Bearer token."
                                            />
                                            <ErrorRow
                                                status="404"
                                                code="not_found"
                                                action="Check the path and confirm the requested record exists."
                                            />
                                            <ErrorRow
                                                status="422"
                                                code="invalid_parameter"
                                                action="Correct the supplied fields before retrying."
                                            />
                                            <ErrorRow
                                                status="429"
                                                code="rate_limit_exceeded"
                                                action="Wait for the supplied retry window."
                                            />
                                            <ErrorRow
                                                status="503"
                                                code="upstream_unavailable"
                                                action="Retry with exponential backoff and jitter."
                                            />
                                        </tbody>
                                    </table>
                                </div>
                            </DocSection>

                            <DocSection
                                id="rate-limits"
                                label="05"
                                title="Rate limits"
                                intro="Burst protection and monthly allowances are separate. Response headers show when a short-term limit resets."
                            >
                                <div className="grid gap-8 sm:grid-cols-[1fr_.9fr]">
                                    <ol className="space-y-5">
                                        <RetryStep number="1">
                                            Read{' '}
                                            <InlineCode>Retry-After</InlineCode>{' '}
                                            when supplied.
                                        </RetryStep>
                                        <RetryStep number="2">
                                            Wait for that delay and add random
                                            jitter.
                                        </RetryStep>
                                        <RetryStep number="3">
                                            Cache results and avoid polling data
                                            that has not changed.
                                        </RetryStep>
                                    </ol>
                                    <CodeBlock
                                        title="Limit headers"
                                        code={`RateLimit-Limit: 60
RateLimit-Remaining: 59
RateLimit-Reset: 12
Retry-After: 12`}
                                        compact
                                    />
                                </div>
                                <p className="mt-6 leading-7 text-slate-600">
                                    Monthly usage is visible in the dashboard.
                                    When the allowance is exhausted, requests
                                    resume at the next monthly reset or after a
                                    plan upgrade.
                                </p>
                            </DocSection>

                            <DocSection
                                id="api-reference"
                                label="06"
                                title="API reference"
                                intro="Every live route is listed below with its purpose, parameters, coverage, source and freshness. Nothing is hidden behind a card."
                            >
                                <div className="flex flex-wrap gap-2">
                                    {endpointFamilies.map((family) => (
                                        <a
                                            key={family.slug}
                                            href={`#${family.slug}`}
                                            className="border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:border-[#2563c7] hover:text-[#2563c7]"
                                        >
                                            {family.name}
                                        </a>
                                    ))}
                                </div>
                                <a
                                    href="/openapi/v1.yaml"
                                    className="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-[#2563c7] hover:underline"
                                >
                                    <FileCode2 className="size-4" /> Download
                                    OpenAPI specification{' '}
                                    <ExternalLink className="size-3.5" />
                                </a>
                                <div className="mt-10 space-y-16">
                                    {endpointFamilies.map((family) => (
                                        <section
                                            key={family.slug}
                                            id={family.slug}
                                            className="scroll-mt-28"
                                        >
                                            <div className="border-b border-slate-950 pb-4">
                                                <h3 className="text-2xl font-semibold tracking-tight">
                                                    {family.name}
                                                </h3>
                                                <p className="mt-2 leading-7 text-slate-600">
                                                    {family.summary}
                                                </p>
                                            </div>
                                            <div className="divide-y divide-slate-300">
                                                {family.endpoints.map(
                                                    (endpoint) => (
                                                        <EndpointReference
                                                            key={endpoint.path}
                                                            endpoint={endpoint}
                                                        />
                                                    ),
                                                )}
                                            </div>
                                        </section>
                                    ))}
                                </div>
                            </DocSection>

                            <DocSection
                                id="sources-and-coverage"
                                label="07"
                                title="Sources & coverage"
                                intro="UKApi.co.uk normalises public data without hiding where it came from or where it applies."
                            >
                                <div className="overflow-x-auto border border-slate-300 bg-white">
                                    <table className="w-full min-w-[680px] text-left text-sm">
                                        <thead className="bg-slate-100 font-mono text-[10px] tracking-wider text-slate-600 uppercase">
                                            <tr>
                                                <th className="px-4 py-3">
                                                    Family
                                                </th>
                                                <th className="px-4 py-3">
                                                    Source
                                                </th>
                                                <th className="px-4 py-3">
                                                    Coverage
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-200">
                                            {endpointFamilies.map((family) => (
                                                <tr
                                                    key={family.slug}
                                                    className="align-top"
                                                >
                                                    <th className="px-4 py-4 font-semibold text-slate-900">
                                                        {family.name}
                                                    </th>
                                                    <td className="px-4 py-4 leading-6 text-slate-600">
                                                        {[
                                                            ...new Set(
                                                                family.endpoints.map(
                                                                    (item) =>
                                                                        item.source,
                                                                ),
                                                            ),
                                                        ].join('; ')}
                                                    </td>
                                                    <td className="px-4 py-4 leading-6 text-slate-600">
                                                        {[
                                                            ...new Set(
                                                                family.endpoints.map(
                                                                    (item) =>
                                                                        item.coverage,
                                                                ),
                                                            ),
                                                        ].join('; ')}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <Callout title="Safety note">
                                    Flood information is informational only and
                                    is not an emergency alert service. Review
                                    the{' '}
                                    <Link
                                        href="/data-sources"
                                        className="font-semibold text-[#2563c7] underline"
                                    >
                                        full source register
                                    </Link>{' '}
                                    for attribution and provider caveats.
                                </Callout>
                            </DocSection>
                        </article>
                    </main>
                </div>
            </div>
        </>
    );
}

function DocsNavigation() {
    return (
        <aside className="border-b border-slate-300 py-4 lg:border-r lg:border-b-0 lg:py-12 lg:pr-6">
            <nav aria-label="On this page" className="sticky top-28">
                <p className="font-mono text-[10px] font-bold tracking-[0.16em] text-slate-500 uppercase">
                    On this page
                </p>
                <div className="mt-3 grid grid-cols-2 gap-1 lg:grid-cols-1 lg:gap-0">
                    {sections.map(([id, label]) => (
                        <a
                            key={id}
                            href={`#${id}`}
                            className="shrink-0 border-l-2 border-transparent px-3 py-2 text-sm font-medium text-slate-600 hover:border-[#2563c7] hover:bg-white hover:text-slate-950"
                        >
                            {label}
                        </a>
                    ))}
                </div>
            </nav>
        </aside>
    );
}

function DocSection({
    id,
    label,
    title,
    intro,
    children,
}: {
    id: string;
    label: string;
    title: string;
    intro: string;
    children: ReactNode;
}) {
    return (
        <section
            id={id}
            className="scroll-mt-28 border-b border-slate-300 py-14 last:border-b-0 lg:py-18"
        >
            <div className="grid gap-4 sm:grid-cols-[3rem_1fr]">
                <span className="font-mono text-xs font-bold text-[#2563c7]">
                    {label}
                </span>
                <div>
                    <h2 className="text-3xl font-semibold tracking-[-0.035em] text-slate-950 sm:text-4xl">
                        {title}
                    </h2>
                    <p className="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                        {intro}
                    </p>
                </div>
            </div>
            <div className="mt-8 sm:ml-12">{children}</div>
        </section>
    );
}

function Step({
    number,
    title,
    children,
}: {
    number: string;
    title: string;
    children: ReactNode;
}) {
    return (
        <li className="border-r border-b border-slate-300 bg-white p-5">
            <span className="font-mono text-xs font-bold text-[#2563c7]">
                {number}
            </span>
            <h3 className="mt-5 text-base font-semibold">{title}</h3>
            <p className="mt-2 text-sm leading-6 text-slate-600">{children}</p>
        </li>
    );
}

function Info({ title, children }: { title: string; children: ReactNode }) {
    return (
        <div>
            <h3 className="text-base font-semibold text-slate-950">{title}</h3>
            <p className="mt-2 text-sm leading-6 text-slate-600">{children}</p>
        </div>
    );
}

function Definition({ term, children }: { term: string; children: ReactNode }) {
    return (
        <div className="border-r border-b border-slate-300 bg-white p-4">
            <dt className="font-mono text-xs font-bold text-[#2563c7]">
                {term}
            </dt>
            <dd className="mt-2 text-sm leading-6 text-slate-600">
                {children}
            </dd>
        </div>
    );
}

function RetryStep({
    number,
    children,
}: {
    number: string;
    children: ReactNode;
}) {
    return (
        <li className="flex gap-3 text-sm leading-6 text-slate-600">
            <span className="grid size-6 shrink-0 place-items-center bg-[#2563c7] font-mono text-xs font-bold text-white">
                {number}
            </span>
            <span>{children}</span>
        </li>
    );
}

function ErrorRow({
    status,
    code,
    action,
}: {
    status: string;
    code: string;
    action: string;
}) {
    return (
        <tr className="align-top">
            <td className="px-4 py-3 font-mono text-xs font-semibold text-slate-700">
                {status}
            </td>
            <td className="px-4 py-3 font-mono text-xs text-slate-700">
                {code}
            </td>
            <td className="px-4 py-3 leading-6">{action}</td>
        </tr>
    );
}

function EndpointReference({ endpoint }: { endpoint: Endpoint }) {
    return (
        <article className="py-7">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-3">
                        <span className="bg-emerald-100 px-2 py-1 font-mono text-[10px] font-bold text-emerald-800">
                            {endpoint.method}
                        </span>
                        <code className="font-mono text-sm font-semibold break-all text-slate-900">
                            {endpoint.path}
                        </code>
                    </div>
                    <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                        {endpoint.description}
                    </p>
                </div>
            </div>
            <dl className="mt-5 grid gap-x-6 gap-y-3 border-l-2 border-slate-200 pl-4 text-sm sm:grid-cols-3">
                <InfoPair term="Coverage" value={endpoint.coverage} />
                <InfoPair term="Source" value={endpoint.source} />
                <InfoPair term="Freshness" value={endpoint.freshness} />
            </dl>
            {endpoint.parameters && endpoint.parameters.length > 0 && (
                <div className="mt-5 overflow-x-auto">
                    <table className="w-full min-w-[560px] text-left text-sm">
                        <thead className="font-mono text-[10px] tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th className="pb-2">Parameter</th>
                                <th className="pb-2">In</th>
                                <th className="pb-2">Description</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {endpoint.parameters.map((parameter) => (
                                <tr key={parameter.name}>
                                    <td className="py-3 pr-4 font-mono text-xs font-semibold">
                                        {parameter.name}
                                        {parameter.required && (
                                            <span className="text-rose-600">
                                                {' '}
                                                *
                                            </span>
                                        )}
                                    </td>
                                    <td className="py-3 pr-4 font-mono text-xs text-slate-500">
                                        {parameter.location}
                                    </td>
                                    <td className="py-3 leading-6 text-slate-600">
                                        {parameter.description}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
            <p className="mt-4 font-mono text-xs text-slate-500">
                Example:{' '}
                <span className="text-slate-800">
                    GET {endpoint.examplePath ?? endpoint.path}
                </span>
            </p>
        </article>
    );
}

function InfoPair({ term, value }: { term: string; value: string }) {
    return (
        <div>
            <dt className="font-mono text-[10px] font-bold tracking-wide text-slate-500 uppercase">
                {term}
            </dt>
            <dd className="mt-1 leading-5 text-slate-700">{value}</dd>
        </div>
    );
}

function Callout({ title, children }: { title: string; children: ReactNode }) {
    return (
        <aside className="mt-7 border-l-2 border-[#2563c7] bg-blue-50 p-5">
            <div className="flex gap-3">
                <ShieldCheck className="mt-0.5 size-5 shrink-0 text-[#2563c7]" />
                <div>
                    <h3 className="text-base font-semibold">{title}</h3>
                    <div className="mt-1 text-sm leading-6 text-slate-700">
                        {children}
                    </div>
                </div>
            </div>
        </aside>
    );
}

function InlineCode({ children }: { children: ReactNode }) {
    return (
        <code className="bg-slate-100 px-1.5 py-0.5 font-mono text-[0.86em] text-slate-800">
            {children}
        </code>
    );
}

function CodeBlock({
    title,
    code,
    compact = false,
}: {
    title: string;
    code: string;
    compact?: boolean;
}) {
    const [copied, setCopied] = useState(false);
    const copy = async () => {
        await navigator.clipboard?.writeText(code);
        setCopied(true);
        window.setTimeout(() => setCopied(false), 1800);
    };
    return (
        <section
            className={`${compact ? '' : 'mt-8'} overflow-hidden border border-slate-800 bg-slate-950`}
        >
            <div className="flex items-center justify-between border-b border-slate-700 px-4 py-3">
                <span className="font-mono text-xs text-slate-400">
                    {title}
                </span>
                <button
                    type="button"
                    onClick={() => void copy()}
                    className="inline-flex items-center gap-1.5 px-1.5 py-1 text-xs font-medium text-slate-400 hover:text-white"
                >
                    {copied ? (
                        <Check className="size-3.5" />
                    ) : (
                        <Copy className="size-3.5" />
                    )}
                    {copied ? 'Copied' : 'Copy'}
                </button>
            </div>
            <pre className="overflow-x-auto p-4 font-mono text-[12px] leading-6 text-blue-100 sm:text-[13px]">
                <code>{code}</code>
            </pre>
        </section>
    );
}
