import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    ChevronDown,
    Copy,
    ExternalLink,
    FileCode2,
    KeyRound,
    Layers3,
    ShieldCheck,
    Zap,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { endpointFamilies, type Endpoint } from '@/lib/ukapi-content';

type DocsProps = {
    section?: string;
};

type NavItem = {
    href: string;
    label: string;
    section?: string;
};

const navigation: { label: string; items: NavItem[] }[] = [
    {
        label: 'Start here',
        items: [
            { href: '/docs', label: 'Introduction', section: 'overview' },
            {
                href: '/docs/getting-started',
                label: 'Getting started',
                section: 'getting-started',
            },
        ],
    },
    {
        label: 'Build with UKAPI.io',
        items: [
            {
                href: '/docs/authentication',
                label: 'Authentication',
                section: 'authentication',
            },
            { href: '/docs/errors', label: 'Errors', section: 'errors' },
            {
                href: '/docs/rate-limits',
                label: 'Rate limits',
                section: 'rate-limits',
            },
        ],
    },
    {
        label: 'Reference',
        items: [
            {
                href: '/docs/api-reference',
                label: 'API reference',
                section: 'api-reference',
            },
            {
                href: '/docs/sources-and-coverage',
                label: 'Sources & coverage',
                section: 'sources-and-coverage',
            },
        ],
    },
];

const pages = navigation.flatMap((group) => group.items);

const pageMeta: Record<string, { title: string; description: string }> = {
    overview: {
        title: 'Documentation',
        description:
            'Everything you need to make reliable requests to the UKAPI.io v1 API.',
    },
    'getting-started': {
        title: 'Getting started',
        description: 'Make your first successful UKAPI.io request in minutes.',
    },
    authentication: {
        title: 'Authentication',
        description: 'Authenticate every API request with a secret API key.',
    },
    errors: {
        title: 'Errors',
        description:
            'Handle errors predictably and make support requests easier.',
    },
    'rate-limits': {
        title: 'Rate limits',
        description:
            'Build integrations that stay reliable as your usage grows.',
    },
    'api-reference': {
        title: 'API reference',
        description:
            'The hand-maintained reference for every live v1 endpoint.',
    },
    'sources-and-coverage': {
        title: 'Sources & coverage',
        description:
            'Know the origin, geography and important caveats of the data.',
    },
};

export default function Docs({ section }: DocsProps) {
    const activeSection = pageMeta[section ?? 'overview']
        ? (section ?? 'overview')
        : 'overview';
    const meta = pageMeta[activeSection];

    return (
        <>
            <Head title={meta.title}>
                <meta name="description" content={meta.description} />
            </Head>
            <div className="border-b border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-5 py-5 lg:px-8">
                    <div className="flex items-center gap-2 text-xs font-medium text-slate-500">
                        <Link
                            href="/"
                            className="rounded-sm hover:text-[#1248e8] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8]"
                        >
                            UKAPI.io
                        </Link>
                        <span aria-hidden="true">/</span>
                        <span className="text-slate-700">Documentation</span>
                    </div>
                </div>
            </div>
            <div className="bg-[#f7f9fc]">
                <div className="mx-auto max-w-7xl px-5 lg:px-8">
                    <div className="border-x border-slate-200 bg-white lg:grid lg:grid-cols-[14.5rem_minmax(0,1fr)]">
                        <DocsNavigation activeSection={activeSection} />
                        <main className="min-w-0 px-5 py-10 sm:px-8 lg:px-12 lg:py-14">
                            <article className="mx-auto max-w-3xl">
                                <p className="text-xs font-bold tracking-[0.16em] text-[#1248e8] uppercase">
                                    UKAPI.io API · v1
                                </p>
                                <h1 className="mt-3 text-4xl font-semibold tracking-[-0.05em] text-slate-950 sm:text-5xl">
                                    {meta.title}
                                </h1>
                                <p className="mt-5 max-w-2xl text-lg leading-8 text-slate-600">
                                    {meta.description}
                                </p>
                                <div className="mt-10">
                                    <DocsContent section={activeSection} />
                                </div>
                                <PageFooter section={activeSection} />
                            </article>
                        </main>
                    </div>
                </div>
            </div>
        </>
    );
}

function DocsNavigation({ activeSection }: { activeSection: string }) {
    return (
        <aside className="border-b border-slate-200 bg-slate-50/60 lg:border-r lg:border-b-0">
            <details className="group lg:hidden">
                <summary className="flex cursor-pointer list-none items-center justify-between px-5 py-4 text-sm font-semibold text-slate-800 [&::-webkit-details-marker]:hidden">
                    <span>Documentation menu</span>
                    <ChevronDown className="size-4 text-slate-500 transition-transform group-open:rotate-180" />
                </summary>
                <div className="border-t border-slate-200 px-3 py-4">
                    <NavigationGroups activeSection={activeSection} />
                </div>
            </details>
            <nav
                aria-label="Documentation"
                className="sticky top-[73px] hidden max-h-[calc(100svh-73px)] overflow-y-auto px-4 py-8 lg:block"
            >
                <NavigationGroups activeSection={activeSection} />
                <a
                    href="/openapi/v1.yaml"
                    className="mt-8 flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-white hover:text-[#1248e8] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8]"
                >
                    <FileCode2 className="size-4" />
                    OpenAPI specification
                    <ExternalLink className="ml-auto size-3.5" />
                </a>
            </nav>
        </aside>
    );
}

function NavigationGroups({ activeSection }: { activeSection: string }) {
    return (
        <div className="space-y-6">
            {navigation.map((group) => (
                <div key={group.label}>
                    <p className="px-3 text-[11px] font-bold tracking-[0.13em] text-slate-400 uppercase">
                        {group.label}
                    </p>
                    <div className="mt-2 space-y-0.5">
                        {group.items.map((item) => {
                            const active = item.section === activeSection;

                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    aria-current={active ? 'page' : undefined}
                                    className={`block rounded-md px-3 py-2 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8] ${active ? 'bg-blue-50 font-semibold text-[#1248e8]' : 'font-medium text-slate-600 hover:bg-white hover:text-slate-950'}`}
                                >
                                    {item.label}
                                </Link>
                            );
                        })}
                    </div>
                </div>
            ))}
        </div>
    );
}

function DocsContent({ section }: { section: string }) {
    switch (section) {
        case 'getting-started':
            return <GettingStarted />;
        case 'authentication':
            return <Authentication />;
        case 'errors':
            return <Errors />;
        case 'rate-limits':
            return <RateLimits />;
        case 'api-reference':
            return <ApiReference />;
        case 'sources-and-coverage':
            return <SourcesAndCoverage />;
        default:
            return <Overview />;
    }
}

function Overview() {
    return (
        <div className="space-y-10">
            <p className="text-base leading-8 text-slate-600">
                UKAPI.io gives you one predictable interface for useful UK data.
                All production requests use versioned{' '}
                <InlineCode>/v1</InlineCode> paths, a single API key, and the
                same response envelope.
            </p>
            <div className="grid gap-4 sm:grid-cols-2">
                <GuideCard
                    icon={Zap}
                    title="Get a first response"
                    text="Create a key, send a postcode lookup, and understand the response shape."
                    href="/docs/getting-started"
                />
                <GuideCard
                    icon={KeyRound}
                    title="Keep your key secure"
                    text="Use a Bearer token from a trusted server or secret manager."
                    href="/docs/authentication"
                />
                <GuideCard
                    icon={ShieldCheck}
                    title="Handle edge cases"
                    text="Build reliable error and retry handling before you go live."
                    href="/docs/errors"
                />
                <GuideCard
                    icon={Layers3}
                    title="Explore the reference"
                    text="Browse live endpoints with their parameters, source and coverage."
                    href="/docs/api-reference"
                />
            </div>
            <section className="border-t border-slate-200 pt-10">
                <h2 className="text-2xl font-semibold tracking-[-0.035em] text-slate-950">
                    API conventions
                </h2>
                <div className="mt-5 space-y-4 text-base leading-7 text-slate-600">
                    <p>
                        Successful requests return a{' '}
                        <InlineCode>data</InlineCode> object and a{' '}
                        <InlineCode>meta</InlineCode> object. The metadata
                        includes a request ID, cache state, the source of
                        provider-backed data and its coverage.
                    </p>
                    <p>
                        Failures return an <InlineCode>error</InlineCode>{' '}
                        object. Use its stable <InlineCode>code</InlineCode>{' '}
                        field in your application logic, and keep the request ID
                        if you need help.
                    </p>
                </div>
                <CodeBlock
                    title="A successful response"
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
            </section>
        </div>
    );
}

function GettingStarted() {
    return (
        <div className="space-y-10">
            <p className="text-base leading-8 text-slate-600">
                You can make a request from any server environment that can send
                HTTPS. Start with a test key and move to a live key only when
                your integration is ready.
            </p>
            <Steps
                steps={[
                    {
                        title: 'Create an account',
                        content: (
                            <>
                                Create a free account, then open the API keys
                                page in your dashboard. Your key is shown
                                once—store it in a password manager or secret
                                store.
                            </>
                        ),
                    },
                    {
                        title: 'Send your first request',
                        content:
                            'Include your key as a Bearer token and ask for JSON. This lookup normalises the postcode before returning its data.',
                    },
                    {
                        title: 'Use the response metadata',
                        content:
                            'Record the request ID in your logs. Source, coverage and cache fields let your product show data honestly to its users.',
                    },
                ]}
            />
            <CodeBlock
                title="cURL"
                code={`curl "https://api.ukapi.io/v1/postcodes/BL2%206XX" \\
  -H "Authorization: Bearer uk_live_your_api_key" \\
  -H "Accept: application/json"`}
            />
            <Callout title="Keep the key on the server">
                Do not put API keys in browser code, mobile apps or public
                source control. Make UKAPI.io requests through your own backend.
            </Callout>
        </div>
    );
}

function Authentication() {
    return (
        <div className="space-y-10">
            <p className="text-base leading-8 text-slate-600">
                Every <InlineCode>/v1</InlineCode> request requires a UKAPI.io
                API key. Send it in the standard{' '}
                <InlineCode>Authorization</InlineCode> header using the Bearer
                scheme.
            </p>
            <CodeBlock
                title="Request headers"
                code={`Authorization: Bearer uk_live_your_api_key
Accept: application/json`}
            />
            <section>
                <h2 className="text-2xl font-semibold tracking-[-0.035em] text-slate-950">
                    Managing keys
                </h2>
                <div className="mt-5 grid gap-4 sm:grid-cols-2">
                    <InfoCard
                        title="Test keys"
                        text="Use these while developing and testing your integration."
                    />
                    <InfoCard
                        title="Live keys"
                        text="Use these only in production, loaded from environment variables or a secret manager."
                    />
                </div>
                <p className="mt-5 text-base leading-7 text-slate-600">
                    You can revoke a key in the dashboard whenever it is no
                    longer needed or you believe it may have been exposed.
                    Create a replacement first so your integration has a safe
                    rotation path.
                </p>
            </section>
            <Callout title="Missing or invalid keys">
                Requests without a valid key receive a{' '}
                <InlineCode>401</InlineCode> response with the{' '}
                <InlineCode>invalid_api_key</InlineCode> error code.
            </Callout>
        </div>
    );
}

function Errors() {
    return (
        <div className="space-y-10">
            <p className="text-base leading-8 text-slate-600">
                Errors use a consistent JSON envelope. Branch on the
                machine-readable error code rather than matching the message
                text.
            </p>
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
            <section>
                <h2 className="text-2xl font-semibold tracking-[-0.035em] text-slate-950">
                    Common responses
                </h2>
                <div className="mt-5 overflow-hidden rounded-xl border border-slate-200">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Code</th>
                                <th className="px-4 py-3">What to do</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200 text-slate-600">
                            <ErrorRow
                                status="401"
                                code="invalid_api_key"
                                action="Check that the key is present, active and sent as a Bearer token."
                            />
                            <ErrorRow
                                status="404"
                                code="not_found"
                                action="Check the path and confirm the requested record exists."
                            />
                            <ErrorRow
                                status="422"
                                code="invalid_parameter"
                                action="Use the field details to correct the request before retrying."
                            />
                            <ErrorRow
                                status="429"
                                code="rate_limit_exceeded"
                                action="Wait for the supplied retry window before retrying."
                            />
                            <ErrorRow
                                status="503"
                                code="upstream_unavailable"
                                action="Retry with exponential backoff; do not treat it as a client error."
                            />
                        </tbody>
                    </table>
                </div>
            </section>
            <Callout title="Support requests">
                Include <InlineCode>meta.request_id</InlineCode> or{' '}
                <InlineCode>error.request_id</InlineCode> when contacting
                support. It lets us locate the request without needing to see
                sensitive data.
            </Callout>
        </div>
    );
}

function RateLimits() {
    return (
        <div className="space-y-10">
            <p className="text-base leading-8 text-slate-600">
                UKAPI.io applies burst protection and a monthly allowance to
                each API key. This keeps the service responsive and makes usage
                predictable across all customers.
            </p>
            <section>
                <h2 className="text-2xl font-semibold tracking-[-0.035em] text-slate-950">
                    When you receive a 429
                </h2>
                <ol className="mt-5 space-y-4 text-base leading-7 text-slate-600">
                    <li className="flex gap-3">
                        <span className="grid size-6 shrink-0 place-items-center rounded-full bg-blue-50 text-xs font-bold text-[#1248e8]">
                            1
                        </span>
                        Read the <InlineCode>Retry-After</InlineCode> header
                        when it is supplied.
                    </li>
                    <li className="flex gap-3">
                        <span className="grid size-6 shrink-0 place-items-center rounded-full bg-blue-50 text-xs font-bold text-[#1248e8]">
                            2
                        </span>
                        Retry after that delay, adding jitter to avoid a
                        thundering herd when many jobs restart together.
                    </li>
                    <li className="flex gap-3">
                        <span className="grid size-6 shrink-0 place-items-center rounded-full bg-blue-50 text-xs font-bold text-[#1248e8]">
                            3
                        </span>
                        Cache responses where appropriate and avoid polling data
                        that has not changed.
                    </li>
                </ol>
            </section>
            <CodeBlock
                title="Useful response headers"
                code={`RateLimit-Limit: 60
RateLimit-Remaining: 59
RateLimit-Reset: 12
Retry-After: 12`}
            />
            <Callout title="Monthly allowance">
                Your plan’s monthly allowance is separate from short-term burst
                protection. You can review your current usage and plan in the
                dashboard.
            </Callout>
        </div>
    );
}

function ApiReference() {
    return (
        <div className="space-y-10">
            <p className="text-base leading-8 text-slate-600">
                All endpoints below are live and require an API key. The OpenAPI
                contract is also available for generated clients and validation.
            </p>
            <a
                href="/openapi/v1.yaml"
                className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-[#1248e8] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8]"
            >
                <FileCode2 className="size-4" />
                Download OpenAPI specification
                <ExternalLink className="size-3.5" />
            </a>
            <div className="space-y-12">
                {endpointFamilies.map((family) => (
                    <section key={family.slug} id={family.slug}>
                        <div className="border-b border-slate-200 pb-4">
                            <h2 className="text-2xl font-semibold tracking-[-0.035em] text-slate-950">
                                {family.name}
                            </h2>
                            <p className="mt-2 leading-7 text-slate-600">
                                {family.summary}
                            </p>
                        </div>
                        <div className="mt-5 space-y-5">
                            {family.endpoints.map((endpoint) => (
                                <EndpointCard
                                    key={endpoint.path}
                                    endpoint={endpoint}
                                />
                            ))}
                        </div>
                    </section>
                ))}
            </div>
        </div>
    );
}

function SourcesAndCoverage() {
    return (
        <div className="space-y-10">
            <p className="text-base leading-8 text-slate-600">
                UKAPI.io normalises useful public data, but it does not obscure
                the source or the limitations of that data. Every
                provider-backed response identifies its source and geographic
                coverage in metadata.
            </p>
            <div className="space-y-4">
                {endpointFamilies.map((family) => {
                    const sources = [
                        ...new Set(family.endpoints.map((item) => item.source)),
                    ];
                    const coverage = [
                        ...new Set(
                            family.endpoints.map((item) => item.coverage),
                        ),
                    ];

                    return (
                        <section
                            key={family.slug}
                            className="rounded-xl border border-slate-200 bg-white p-5"
                        >
                            <h2 className="font-semibold text-slate-950">
                                {family.name}
                            </h2>
                            <dl className="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="font-medium text-slate-500">
                                        Source
                                    </dt>
                                    <dd className="mt-1 leading-6 text-slate-700">
                                        {sources.join('; ')}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="font-medium text-slate-500">
                                        Coverage
                                    </dt>
                                    <dd className="mt-1 leading-6 text-slate-700">
                                        {coverage.join('; ')}
                                    </dd>
                                </div>
                            </dl>
                        </section>
                    );
                })}
            </div>
            <Callout title="Important">
                Flood information is informational only and is not an emergency
                alert service. Read the{' '}
                <Link
                    href="/data-sources"
                    className="font-semibold text-[#1248e8] hover:text-[#0f3dc4]"
                >
                    data sources and attribution
                </Link>{' '}
                register for the full provider list and attribution
                requirements.
            </Callout>
        </div>
    );
}

function EndpointCard({ endpoint }: { endpoint: Endpoint }) {
    const requestPath = endpoint.examplePath ?? endpoint.path;
    const example = `curl "https://api.ukapi.io${requestPath}" \\
  -H "Authorization: Bearer uk_live_your_api_key" \\
  -H "Accept: application/json"`;

    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div className="p-5">
                <div className="flex flex-wrap items-center gap-3">
                    <span className="rounded-md bg-emerald-50 px-2 py-1 font-mono text-xs font-bold text-emerald-700">
                        {endpoint.method}
                    </span>
                    <code className="font-mono text-sm font-medium break-all text-slate-900">
                        {endpoint.path}
                    </code>
                </div>
                <p className="mt-4 leading-7 text-slate-600">
                    {endpoint.description}
                </p>
                <dl className="mt-5 grid gap-3 border-t border-slate-100 pt-4 text-sm sm:grid-cols-3">
                    <div>
                        <dt className="font-medium text-slate-500">Coverage</dt>
                        <dd className="mt-1 text-slate-700">
                            {endpoint.coverage}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-medium text-slate-500">Source</dt>
                        <dd className="mt-1 text-slate-700">
                            {endpoint.source}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-medium text-slate-500">
                            Freshness
                        </dt>
                        <dd className="mt-1 text-slate-700">
                            {endpoint.freshness}
                        </dd>
                    </div>
                </dl>
            </div>
            {endpoint.parameters && endpoint.parameters.length > 0 && (
                <div className="border-t border-slate-200 px-5 py-5">
                    <h3 className="text-sm font-semibold text-slate-950">
                        Parameters
                    </h3>
                    <div className="mt-3 overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <thead className="text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                <tr>
                                    <th className="pr-4 pb-2">Name</th>
                                    <th className="pr-4 pb-2">In</th>
                                    <th className="pb-2">Description</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 text-slate-600">
                                {endpoint.parameters.map((parameter) => (
                                    <tr key={parameter.name}>
                                        <td className="py-3 pr-4 font-mono text-xs font-medium text-slate-800">
                                            {parameter.name}
                                            {parameter.required && (
                                                <span className="ml-1 text-rose-600">
                                                    *
                                                </span>
                                            )}
                                        </td>
                                        <td className="py-3 pr-4 font-mono text-xs text-slate-500">
                                            {parameter.location}
                                        </td>
                                        <td className="py-3 leading-6">
                                            {parameter.description}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
            <div className="border-t border-slate-200 bg-slate-950 p-1">
                <CodeBlock title="Example request" code={example} compact />
            </div>
            {endpoint.exampleResponse && (
                <div className="border-t border-slate-700 bg-slate-950 p-1">
                    <CodeBlock
                        title="Example response"
                        code={endpoint.exampleResponse}
                        compact
                    />
                </div>
            )}
        </section>
    );
}

function Steps({ steps }: { steps: { title: string; content: ReactNode }[] }) {
    return (
        <ol className="space-y-0">
            {steps.map((step, index) => (
                <li
                    key={step.title}
                    className="relative flex gap-4 pb-8 last:pb-0"
                >
                    {index < steps.length - 1 && (
                        <span className="absolute top-8 left-4 h-[calc(100%-1rem)] border-l border-slate-200" />
                    )}
                    <span className="z-10 grid size-8 shrink-0 place-items-center rounded-full bg-[#1248e8] text-sm font-bold text-white">
                        {index + 1}
                    </span>
                    <div className="pt-0.5">
                        <h2 className="font-semibold text-slate-950">
                            {step.title}
                        </h2>
                        <div className="mt-2 leading-7 text-slate-600">
                            {step.content}
                        </div>
                    </div>
                </li>
            ))}
        </ol>
    );
}

function GuideCard({
    icon: Icon,
    title,
    text,
    href,
}: {
    icon: typeof Zap;
    title: string;
    text: string;
    href: string;
}) {
    return (
        <Link
            href={href}
            className="group rounded-xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-950/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8]"
        >
            <Icon className="size-5 text-[#1248e8]" />
            <h2 className="mt-5 flex items-center justify-between font-semibold text-slate-950">
                {title}
                <ArrowRight className="size-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-[#1248e8]" />
            </h2>
            <p className="mt-2 text-sm leading-6 text-slate-600">{text}</p>
        </Link>
    );
}

function InfoCard({ title, text }: { title: string; text: string }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5">
            <h3 className="font-semibold text-slate-950">{title}</h3>
            <p className="mt-2 text-sm leading-6 text-slate-600">{text}</p>
        </div>
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

function Callout({ title, children }: { title: string; children: ReactNode }) {
    return (
        <aside className="rounded-xl border border-blue-100 bg-blue-50/70 p-5">
            <div className="flex gap-3">
                <ShieldCheck className="mt-0.5 size-5 shrink-0 text-[#1248e8]" />
                <div>
                    <h2 className="font-semibold text-slate-950">{title}</h2>
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
        <code className="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[0.86em] text-slate-800">
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
            className={
                compact
                    ? 'overflow-hidden rounded-lg'
                    : 'mt-8 overflow-hidden rounded-xl border border-slate-800 bg-slate-950'
            }
        >
            <div className="flex items-center justify-between border-b border-slate-800 px-4 py-3">
                <span className="font-mono text-xs text-slate-400">
                    {title}
                </span>
                <button
                    type="button"
                    onClick={() => void copy()}
                    className="inline-flex items-center gap-1.5 rounded px-1.5 py-1 text-xs font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
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

function PageFooter({ section }: { section: string }) {
    const currentIndex = pages.findIndex((page) => page.section === section);
    const previous = currentIndex > 0 ? pages[currentIndex - 1] : undefined;
    const next = currentIndex >= 0 ? pages[currentIndex + 1] : pages[0];

    return (
        <nav
            aria-label="Documentation pagination"
            className="mt-16 flex items-stretch justify-between gap-4 border-t border-slate-200 pt-6"
        >
            {previous ? (
                <Link
                    href={previous.href}
                    className="group rounded-md py-2 text-sm font-medium text-slate-600 transition hover:text-[#1248e8] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8]"
                >
                    <span className="flex items-center gap-1 text-xs text-slate-400 group-hover:text-[#1248e8]">
                        <ArrowLeft className="size-3.5" /> Previous
                    </span>
                    <span className="mt-1 block">{previous.label}</span>
                </Link>
            ) : (
                <span />
            )}
            {next && (
                <Link
                    href={next.href}
                    className="group rounded-md py-2 text-right text-sm font-medium text-slate-600 transition hover:text-[#1248e8] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1248e8]"
                >
                    <span className="flex items-center justify-end gap-1 text-xs text-slate-400 group-hover:text-[#1248e8]">
                        Next <ArrowRight className="size-3.5" />
                    </span>
                    <span className="mt-1 block">{next.label}</span>
                </Link>
            )}
        </nav>
    );
}
