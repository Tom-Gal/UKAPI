import { Head, usePage } from '@inertiajs/react';
import { FileWarning } from 'lucide-react';

const documents: Record<
    string,
    { title: string; summary: string; sections: [string, string][] }
> = {
    terms: {
        title: 'Terms of service',
        summary:
            'Draft document — pending review and replacement by UKApi.co.uk’s legal team.',
        sections: [
            [
                'Service scope',
                'UKApi.co.uk is an independent developer service that normalises selected UK data sources. Availability, freshness, and geographic scope are documented per endpoint and are not warranties.',
            ],
            [
                'Acceptable use',
                'Use must comply with applicable law, source terms, published rate limits, and the Acceptable Use Policy. Customers must keep API credentials confidential.',
            ],
            [
                'Draft status',
                'This placeholder is not the final contractual terms and must be replaced before public launch.',
            ],
        ],
    },
    privacy: {
        title: 'Privacy notice',
        summary:
            'Draft document — pending review and replacement by UKApi.co.uk’s legal team.',
        sections: [
            [
                'Account information',
                'UKApi.co.uk needs account information to provide dashboard access, API keys, support and operational communications.',
            ],
            [
                'Operational data',
                'Aggregate usage may be retained for billing and analytics. Detailed request logs should be short-lived, and API secrets, authorization headers and raw sensitive query data must not be retained.',
            ],
            [
                'Draft status',
                'This placeholder is not the final privacy notice and must be replaced before public launch.',
            ],
        ],
    },
    'acceptable-use': {
        title: 'Acceptable use policy',
        summary:
            'Draft document — pending review and replacement by UKApi.co.uk’s legal team.',
        sections: [
            [
                'Respect sources',
                'Do not use the platform to evade provider terms, recreate restricted datasets at scale, or imply a government affiliation.',
            ],
            [
                'Protect the service',
                'Do not share credentials, bypass rate or quota limits, probe for vulnerabilities, or use the API for unlawful or high-risk regulated decisioning.',
            ],
            [
                'Draft status',
                'This placeholder is not the final policy and must be replaced before public launch.',
            ],
        ],
    },
};

export default function Legal() {
    const key = usePage().url.split('/').filter(Boolean).at(-1) ?? 'terms';
    const document = documents[key] ?? documents.terms;
    return (
        <>
            <Head title={document.title} />
            <article className="atlas-shell max-w-4xl py-16 lg:py-24">
                <div className="border border-amber-800 bg-amber-50 p-5 text-sm leading-6 text-amber-950 shadow-[4px_4px_0_#d6c59b]">
                    <FileWarning className="mb-2 size-5" />
                    <strong>Draft legal content.</strong> This page is
                    intentionally marked as a placeholder until the legal team
                    supplies approved copy.
                </div>
                <p className="atlas-kicker mt-12">Legal</p>
                <h1 className="mt-4 text-5xl font-semibold tracking-[-.045em]">
                    {document.title}
                </h1>
                <p className="mt-4 text-lg leading-8 text-slate-600">
                    {document.summary}
                </p>
                <div className="mt-10 divide-y divide-slate-300 border border-slate-950 bg-white">
                    {document.sections.map(([title, text]) => (
                        <section key={title} className="p-6">
                            <h2 className="font-sans font-bold">{title}</h2>
                            <p className="mt-3 leading-7 text-slate-600">
                                {text}
                            </p>
                        </section>
                    ))}
                </div>
            </article>
        </>
    );
}
