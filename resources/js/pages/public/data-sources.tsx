import { Head } from '@inertiajs/react';
import { ExternalLink, FileWarning } from 'lucide-react';
import { PageHero } from '@/pages/public/api-catalogue';

const sources = [
    [
        'Postcodes.io',
        'https://postcodes.io/docs/overview/',
        'UK',
        'Postcode and coordinate lookups',
    ],
    [
        'Companies House',
        'https://developer.company-information.service.gov.uk/',
        'UK',
        'Company profiles, officer and filing metadata; locally refreshed condensed SIC 2007 reference snapshot',
    ],
    [
        'GOV.UK Bank Holidays',
        'https://www.gov.uk/bank-holidays.json',
        'UK',
        'Bank-holiday data',
    ],
    [
        'Food Standards Agency',
        'https://api.ratings.food.gov.uk/help',
        'UK',
        'Food hygiene data',
    ],
    [
        'Police.uk',
        'https://data.police.uk/docs/',
        'Provider-specific partial coverage',
        'Approximate street-level crime data',
    ],
    [
        'Environment Agency',
        'https://environment.data.gov.uk/flood-monitoring/doc/reference',
        'England',
        'Flood warnings and monitoring',
    ],
    [
        'Planning Data',
        'https://www.planning.data.gov.uk/docs',
        'England',
        'Planning constraints; beta source',
    ],
];

export default function DataSources() {
    return (
        <>
            <Head title="Data sources & attribution">
                <meta
                    name="description"
                    content="UKAPI.io source, attribution and coverage register."
                />
            </Head>
            <PageHero
                eyebrow="Data sources & attribution"
                title="Public data still has provenance, terms and limits."
                description="UKAPI.io is a normalisation layer, not the authority for these datasets. Coverage and freshness are exposed alongside endpoint responses."
            />
            <div className="mx-auto max-w-6xl px-5 pb-20 lg:px-8">
                <div className="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-950">
                    <FileWarning className="mb-2 size-5" />
                    <strong>Pre-launch register.</strong> Source terms, licence
                    summaries, reuse rights, mandatory attribution wording and
                    review dates must be completed and legally reviewed before
                    each provider is activated.
                </div>
                <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table className="w-full min-w-[760px] text-left text-sm">
                        <thead className="bg-slate-50 text-xs tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th className="px-5 py-3">Source</th>
                                <th className="px-5 py-3">Coverage</th>
                                <th className="px-5 py-3">Used for</th>
                                <th className="px-5 py-3">Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            {sources.map(([name, url, coverage, use]) => (
                                <tr
                                    key={name}
                                    className="border-t border-slate-100"
                                >
                                    <td className="px-5 py-4 font-medium">
                                        {name}
                                    </td>
                                    <td className="px-5 py-4 text-slate-600">
                                        {coverage}
                                    </td>
                                    <td className="px-5 py-4 text-slate-600">
                                        {use}
                                    </td>
                                    <td className="px-5 py-4">
                                        <a
                                            href={url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1 font-semibold text-[#1248e8]"
                                        >
                                            Source docs{' '}
                                            <ExternalLink className="size-3.5" />
                                        </a>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <p className="mt-6 text-sm leading-6 text-slate-500">
                    Planning and property results are informational only and are
                    not a substitute for official searches or professional
                    advice. “Publicly accessible” does not automatically mean
                    commercially redistributable.
                </p>
            </div>
        </>
    );
}
