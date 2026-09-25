import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { PageHero } from '@/pages/public/api-catalogue';
import { endpointFamilies } from '@/lib/ukapi-content';

const guideCopy: Record<
    string,
    { title: string; description: string; sections: [string, string][] }
> = {
    quickstart: {
        title: 'Quickstart',
        description:
            'Create an account, create a test key, and make a real request to a live /v1 endpoint.',
        sections: [
            [
                '1. Create an account',
                'Register for a UKAPI.io account and verify your email address.',
            ],
            [
                '2. Create a test key',
                'From API keys, name a test key and copy it once. Its secret is never shown again.',
            ],
            [
                '3. Make a request',
                'Send Authorization: Bearer uk_test_… to GET /v1/postcodes/BL2%206XX, or use the in-browser console on a live endpoint reference.',
            ],
        ],
    },
    authentication: {
        title: 'Authentication',
        description:
            'Customer API requests use purpose-built keys, not browser sessions.',
        sections: [
            [
                'Bearer token',
                'Send Authorization: Bearer uk_live_… or uk_test_… with every customer API request.',
            ],
            [
                'Key safety',
                'Keys are displayed only on creation and stored as hashes. Revoke a key immediately if it may have been exposed.',
            ],
            [
                'Dashboard access',
                'The dashboard uses a secure Laravel web session and CSRF protection.',
            ],
        ],
    },
    errors: {
        title: 'Errors',
        description:
            'Errors are stable, machine-readable and never expose raw provider failures.',
        sections: [
            [
                'Envelope',
                '{ error: { code, message, status, request_id } } is returned for every failed API request.',
            ],
            [
                'Useful codes',
                'invalid_api_key, api_key_revoked, quota_exceeded, rate_limit_exceeded, invalid_parameter, invalid_postcode, postcode_not_found, invalid_company_number, company_not_found, invalid_sic_code, sic_not_found, sic_reference_unavailable, invalid_coordinates, flood_station_not_found, upstream_unavailable and upstream_invalid_response.',
            ],
            [
                'Support context',
                'Keep the request_id when contacting support; do not send an API key.',
            ],
        ],
    },
    'rate-limits': {
        title: 'Rate limits',
        description:
            'Burst limits protect providers; monthly quotas keep plans predictable.',
        sections: [
            [
                'Burst limits',
                'Starting plan limits are 5, 10, 25 and 50 requests/second for Free, Hobby, Pro and Scale.',
            ],
            [
                'Monthly quota',
                'Starting quotas are 5,000, 50,000, 250,000 and 1,000,000 requests/month.',
            ],
            [
                'Responses',
                '429 uses the standard error envelope and includes rate-limit headers where appropriate.',
            ],
        ],
    },
    pagination: {
        title: 'Pagination',
        description:
            'List endpoints will use one UKAPI.io pagination shape regardless of the upstream source.',
        sections: [
            [
                'List metadata',
                'meta.pagination contains page, per_page and total when page pagination is used.',
            ],
            [
                'Limits',
                'per_page is capped to protect the service and its providers.',
            ],
            [
                'Forward compatibility',
                'Clients should tolerate nullable additions and new enum values within /v1.',
            ],
        ],
    },
    'sources-and-coverage': {
        title: 'Sources & coverage',
        description:
            'Coverage and provenance are part of the response contract, not a footnote.',
        sections: [
            [
                'Coverage',
                'Metadata clearly labels UK, Great Britain, England and Wales, England, and provider-specific partial coverage.',
            ],
            [
                'Attribution',
                'Every endpoint includes source attribution and the data-sources register records licence and reuse review information.',
            ],
            [
                'Freshness',
                'Source timestamps and documented cache policies let your application decide what is acceptable.',
            ],
        ],
    },
};

export default function DocsSection() {
    const slug = usePage().url.split('/').filter(Boolean).at(-1) ?? '';
    const family = endpointFamilies.find((item) => item.slug === slug);
    const guide = guideCopy[slug];
    if (family) return <FamilyReference family={family} />;
    if (guide) return <Guide guide={guide} />;
    return (
        <>
            <Head title="Documentation" />
            <div className="mx-auto max-w-6xl px-5 py-20 lg:px-8">
                <h1 className="text-3xl font-semibold">Reference not found</h1>
                <Link
                    href="/docs"
                    className="mt-5 inline-flex text-sm font-semibold text-[#1248e8]"
                >
                    Back to documentation
                </Link>
            </div>
        </>
    );
}

function FamilyReference({
    family,
}: {
    family: (typeof endpointFamilies)[number];
}) {
    return (
        <>
            <Head title={`${family.name} API`} />
            <PageHero
                eyebrow="API reference"
                title={family.name}
                description={family.summary}
            />
            <div className="mx-auto max-w-6xl px-5 pb-20 lg:px-8">
                <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table className="w-full min-w-[720px] text-left text-sm">
                        <thead className="border-b border-slate-200 bg-slate-50 text-xs tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th className="px-5 py-3">Endpoint</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3">Coverage</th>
                                <th className="px-5 py-3">Freshness</th>
                                <th className="px-5 py-3">
                                    <span className="sr-only">
                                        Reference link
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {family.endpoints.map((endpoint, index) => (
                                <tr
                                    key={`${endpoint.path}-${index}`}
                                    className="border-b border-slate-100 last:border-0"
                                >
                                    <td className="px-5 py-4">
                                        <p className="font-mono text-xs">
                                            <span className="mr-2 text-[#1248e8]">
                                                GET
                                            </span>
                                            {endpoint.path}
                                        </p>
                                        <p className="mt-1 text-slate-600">
                                            {endpoint.description}
                                        </p>
                                    </td>
                                    <td className="px-5 py-4">
                                        <span className="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">
                                            Live
                                        </span>
                                    </td>
                                    <td className="px-5 py-4 text-slate-600">
                                        {endpoint.coverage}
                                    </td>
                                    <td className="px-5 py-4 text-slate-600">
                                        {endpoint.freshness}
                                    </td>
                                    <td className="px-5 py-4">
                                        <Link
                                            href={`/docs/api/${family.slug}/${index + 1}`}
                                            className="inline-flex items-center gap-1 font-semibold text-[#1248e8]"
                                        >
                                            Details{' '}
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <p className="mt-5 text-sm text-slate-500">
                    Source: {family.endpoints[0]?.source}. Refer to the data
                    sources page for attribution.
                </p>
            </div>
        </>
    );
}

function Guide({ guide }: { guide: (typeof guideCopy)[string] }) {
    return (
        <>
            <Head title={guide.title} />
            <PageHero
                eyebrow="Documentation guide"
                title={guide.title}
                description={guide.description}
            />
            <div className="mx-auto max-w-3xl px-5 pb-20 lg:px-8">
                <Link
                    href="/docs"
                    className="inline-flex items-center gap-1 text-sm font-semibold text-[#1248e8]"
                >
                    <ArrowLeft className="size-4" />
                    All documentation
                </Link>
                <div className="mt-8 divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white">
                    {guide.sections.map(([title, body]) => (
                        <section key={title} className="p-6">
                            <h2 className="font-semibold">{title}</h2>
                            <p className="mt-2 leading-7 text-slate-600">
                                {body}
                            </p>
                        </section>
                    ))}
                </div>
            </div>
        </>
    );
}
